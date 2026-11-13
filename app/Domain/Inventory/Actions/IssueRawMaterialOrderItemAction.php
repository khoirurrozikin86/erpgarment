<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{InventoryLot, MaterialIssueDocument, Product, ProductStock, ProductionOrderMaterial, RawMaterialOrderItem, StockMovement};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IssueRawMaterialOrderItemAction
{
    public function __construct(private CompanyContext $company, private DocumentNumberService $numbers, private AuditLogService $audit) {}

    public function __invoke(RawMaterialOrderItem $source, float $quantity, string $issueDate, int $userId): MaterialIssueDocument
    {
        return $this->issueMany([['raw_material_order_item_id' => $source->id, 'quantity' => $quantity]], $issueDate, $userId);
    }

    /** @param array<int, array{raw_material_order_item_id:int, quantity:float}> $lines */
    public function issueMany(array $lines, string $issueDate, int $userId): MaterialIssueDocument
    {
        return DB::transaction(function () use ($lines, $issueDate, $userId) {
            $lines = collect($lines)->map(fn (array $line) => [
                'raw_material_order_item_id' => (int) $line['raw_material_order_item_id'],
                'quantity' => round((float) $line['quantity'], 4),
            ])->filter(fn (array $line) => $line['quantity'] > 0)->values();

            if ($lines->isEmpty() || $lines->pluck('raw_material_order_item_id')->unique()->count() !== $lines->count()) {
                throw ValidationException::withMessages(['items' => __('ui.material_issue.quantity_invalid')]);
            }

            $ids = $lines->pluck('raw_material_order_item_id')->sort()->values();
            $sources = RawMaterialOrderItem::query()->where('company_id', $this->company->id())
                ->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($sources->count() !== $ids->count()) abort(404);
            if ($sources->pluck('raw_material_order_document_id')->unique()->count() !== 1) {
                throw ValidationException::withMessages(['items' => __('ui.material_issue.same_order_required')]);
            }

            $materialIds = $sources->pluck('production_order_material_id')->unique()->sort()->values();
            $materials = ProductionOrderMaterial::query()->whereIn('id', $materialIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($materials->count() !== $materialIds->count()) {
                throw ValidationException::withMessages(['source' => __('ui.material_issue.source_material_missing')]);
            }
            $productIds = $sources->pluck('product_id')->unique()->sort()->values();
            $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $stocks = ProductStock::query()->whereIn('product_id', $productIds)->orderBy('product_id')->lockForUpdate()->get()->keyBy('product_id');

            $sourceBalances = [];
            $materialBalances = [];
            $stockBalances = [];
            foreach ($lines as $line) {
                $source = $sources->get($line['raw_material_order_item_id']);
                $material = $materials->get($source->production_order_material_id);
                $product = $products->get($source->product_id);
                $stock = $stocks->get($source->product_id);
                $quantity = $line['quantity'];
                $sourceIssued = $sourceBalances[$source->id] ?? (float) $source->issued_quantity;
                $materialIssued = $materialBalances[$material->id] ?? (float) $material->issued_quantity;
                $stockQuantity = $stockBalances[$source->product_id] ?? (float) ($stock?->quantity ?? 0);
                $sourceRemaining = round((float) $source->quantity - $sourceIssued, 4);
                $materialRemaining = round((float) $material->required_quantity - $materialIssued, 4);
                if ($quantity <= 0 || $quantity > $sourceRemaining) {
                    throw ValidationException::withMessages(['items' => __('ui.material_issue.quantity_invalid')]);
                }
                if ($quantity > $materialRemaining) {
                    throw ValidationException::withMessages(['items' => __('ui.workflow.production_order_material_limit')]);
                }
                if (!$product || !$product->track_stock) {
                    throw ValidationException::withMessages(['stock' => __('ui.workflow.material_issue_stock_not_tracked')]);
                }
                if (!$stock || $stockQuantity < $quantity) {
                    throw ValidationException::withMessages(['stock' => __('ui.workflow.material_issue_insufficient_stock', ['item' => $product->name, 'available' => number_format((float) ($stock?->quantity ?? 0), 4, ',', '.')])]);
                }
                $sourceBalances[$source->id] = round($sourceIssued + $quantity, 4);
                $materialBalances[$material->id] = round($materialIssued + $quantity, 4);
                $stockBalances[$source->product_id] = round($stockQuantity - $quantity, 4);
            }

            $firstSource = $sources->get($ids->first());
            $document = MaterialIssueDocument::query()->create([
                'company_id' => $this->company->id(),
                'raw_material_order_document_id' => $firstSource->raw_material_order_document_id,
                'number' => $this->numbers->generate('material_issue'),
                'issue_date' => $issueDate,
                'created_by' => $userId,
            ]);

            $lotsByProduct = [];
            foreach ($lines as $line) {
                $source = $sources->get($line['raw_material_order_item_id']);
                $product = $products->get($source->product_id);
                $stock = $stocks->get($source->product_id);
                $quantity = $line['quantity'];
                $lots = $lotsByProduct[$product->id] ??= InventoryLot::query()->where('product_id', $product->id)
                    ->where('current_quantity', '>', 0)->orderBy('received_at')->orderBy('id')->lockForUpdate()->get();
                $lotBalance = $lots->sum(fn (InventoryLot $lot) => (float) $lot->current_quantity);
                if ($lotBalance + 0.0000001 < $quantity) {
                    throw ValidationException::withMessages(['stock' => __('ui.workflow.lot_stock_balance_mismatch', ['item' => $product->name])]);
                }

                $issueItem = $document->items()->create([
                    'raw_material_order_item_id' => $source->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ]);
                $allocations = [];
                $remaining = $quantity;
                foreach ($lots as $lot) {
                    if ($remaining <= 0) break;
                    $allocated = min((float) $lot->current_quantity, $remaining);
                    if ($allocated <= 0) continue;
                    $lot->update(['current_quantity' => round((float) $lot->current_quantity - $allocated, 4)]);
                    $allocations[] = ['inventory_lot_id' => $lot->id, 'quantity' => -$allocated, 'unit_cost' => $lot->unit_cost];
                    $remaining = round($remaining - $allocated, 4);
                }

                $after = round((float) $stock->quantity, 4);
                $before = $after;
                $after = round($before - $quantity, 4);
                $stock->update(['quantity' => $after]);
                $source->update(['issued_quantity' => round((float) $source->issued_quantity + $quantity, 4)]);
                $material = $materials->get($source->production_order_material_id);
                $material->update(['issued_quantity' => round((float) $material->issued_quantity + $quantity, 4)]);
                $unitCost = (float) $stock->average_unit_cost > 0 ? (float) $stock->average_unit_cost : (float) $product->purchase_price;
                $movement = StockMovement::query()->create([
                    'product_id' => $product->id,
                    'material_issue_document_id' => $document->id,
                    'material_issue_item_id' => $issueItem->id,
                    'created_by' => $userId,
                    'movement_type' => 'material_issue',
                    'quantity' => -$quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'cost_amount' => round($quantity * $unitCost, 2),
                    'notes' => __('ui.material_issue.stock_reference', ['number' => $source->number]),
                ]);
                $movement->lotAllocations()->createMany($allocations);
            }

            $document->load('items.product.unit');
            $this->audit->log(
                action: 'CREATE', module: 'MATERIAL_ISSUE',
                description: __('ui.material_issue.audit', ['number' => $document->number, 'request' => $firstSource->document()->value('number')]),
                model: $document, oldValues: null, newValues: $document->toArray(), userId: $userId,
            );

            return $document;
        });
    }
}

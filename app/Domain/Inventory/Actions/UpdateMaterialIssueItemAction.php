<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\{InventoryLot, MaterialIssueItem, Product, ProductStock, RawMaterialOrderItem, StockMovement};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateMaterialIssueItemAction
{
    public function __construct(
        private CompanyContext $company,
        private RestoreMaterialIssueItemAction $restore,
        private AuditLogService $audit,
    ) {}

    public function __invoke(MaterialIssueItem $issueItem, float $quantity, string $issueDate, int $userId): MaterialIssueItem
    {
        return DB::transaction(function () use ($issueItem, $quantity, $issueDate, $userId) {
            $item = MaterialIssueItem::query()->with(['document', 'rawMaterialOrderItem.sourceMaterial'])
                ->lockForUpdate()->findOrFail($issueItem->id);
            if (!$item->raw_material_order_item_id || (int) $item->document->company_id !== $this->company->id()) abort(404);

            $quantity = round($quantity, 4);
            if ($quantity <= 0) throw ValidationException::withMessages(['quantity' => __('ui.material_issue.quantity_invalid')]);
            $oldValues = ['quantity' => (float) $item->quantity, 'issue_date' => $item->document->issue_date->format('Y-m-d')];
            $oldQuantity = (float) $item->quantity;

            if (abs($quantity - $oldQuantity) > 0.0001) {
                [, $source, $orderMaterial, $stock] = ($this->restore)($item, $userId);
                /** @var RawMaterialOrderItem $source */
                $source->refresh();
                $orderMaterial->refresh();
                $stock->refresh();

                $sourceRemaining = round((float) $source->quantity - (float) $source->issued_quantity, 4);
                $moRemaining = round((float) $orderMaterial->required_quantity - (float) $orderMaterial->issued_quantity, 4);
                if ($quantity > $sourceRemaining || $quantity > $moRemaining) {
                    throw ValidationException::withMessages(['quantity' => __('ui.material_issue.quantity_invalid')]);
                }

                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                if (!$product->track_stock || (float) $stock->quantity + 0.0000001 < $quantity) {
                    throw ValidationException::withMessages(['stock' => __('ui.workflow.material_issue_insufficient_stock', ['item' => $product->name, 'available' => number_format((float) $stock->quantity, 4, ',', '.')])]);
                }
                $lots = InventoryLot::query()->where('product_id', $product->id)->where('current_quantity', '>', 0)
                    ->orderBy('received_at')->orderBy('id')->lockForUpdate()->get();
                if ($lots->sum(fn (InventoryLot $lot) => (float) $lot->current_quantity) + 0.0000001 < $quantity) {
                    throw ValidationException::withMessages(['stock' => __('ui.workflow.lot_stock_balance_mismatch', ['item' => $product->name])]);
                }

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

                $before = round((float) $stock->quantity, 4);
                $after = round($before - $quantity, 4);
                $unitCost = (float) $stock->average_unit_cost > 0 ? (float) $stock->average_unit_cost : (float) $product->purchase_price;
                $stock->update(['quantity' => $after]);
                $source->update(['issued_quantity' => round((float) $source->issued_quantity + $quantity, 4)]);
                $orderMaterial->update(['issued_quantity' => round((float) $orderMaterial->issued_quantity + $quantity, 4)]);
                $item->update(['quantity' => $quantity]);

                $movement = StockMovement::query()->create([
                    'product_id' => $product->id,
                    'material_issue_document_id' => $item->material_issue_document_id,
                    'material_issue_item_id' => $item->id,
                    'created_by' => $userId,
                    'movement_type' => 'material_issue',
                    'quantity' => -$quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'cost_amount' => round($quantity * $unitCost, 2),
                    'notes' => __('ui.material_issue.edited_issue_reference', ['number' => $item->document->number]),
                ]);
                $movement->lotAllocations()->createMany($allocations);
            }

            $item->document->update(['issue_date' => $issueDate]);
            $newValues = ['quantity' => $quantity, 'issue_date' => $issueDate];
            $this->audit->log(
                action: 'UPDATE', module: 'MATERIAL_ISSUE',
                description: __('ui.material_issue.updated', ['number' => $item->document->number]),
                model: $item->document,
                oldValues: $oldValues, newValues: $newValues, userId: $userId,
            );

            return $item->fresh(['document', 'product.unit']);
        });
    }
}

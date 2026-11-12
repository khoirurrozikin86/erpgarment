<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductionOrderMaterial;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IssueMaterialRequestAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(MaterialRequest $request, array $lines, int $userId): MaterialRequest
    {
        return DB::transaction(function () use ($request, $lines, $userId) {
            $request = MaterialRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($request->status !== 'approved') {
                throw ValidationException::withMessages(['status' => __('ui.workflow.material_issue_requires_approval')]);
            }

            $items = MaterialRequestItem::query()->where('material_request_id', $request->id)
                ->orderBy('product_id')->lockForUpdate()->get()->keyBy('id');
            usort($lines, fn (array $left, array $right) =>
                (($items->get((int) $left['material_request_item_id'])?->product_id ?? PHP_INT_MAX)
                    <=> ($items->get((int) $right['material_request_item_id'])?->product_id ?? PHP_INT_MAX))
                ?: ((int) $left['material_request_item_id'] <=> (int) $right['material_request_item_id'])
            );
            $productIds = collect($lines)->pluck('material_request_item_id')->map(fn ($id) => $items->get((int) $id)?->product_id)->filter()->unique()->sort()->values();
            $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($lines as $line) {
                $item = $items->get((int) $line['material_request_item_id']);
                if (!$item) {
                    throw ValidationException::withMessages(['items' => __('ui.workflow.material_issue_invalid_item')]);
                }
                $quantity = round((float) $line['quantity'], 4);
                $remaining = round((float) $item->quantity - (float) $item->issued_quantity, 4);
                if ($quantity > $remaining) {
                    throw ValidationException::withMessages(['items' => __('ui.workflow.material_issue_exceeds_remaining', ['item' => $item->product?->name ?? $item->product_id])]);
                }

                $product = $products->get($item->product_id);
                if (!$product || !$product->track_stock) {
                    throw ValidationException::withMessages(['items' => __('ui.workflow.material_issue_stock_not_tracked')]);
                }
                $stock = ProductStock::query()->where('product_id', $product->id)->lockForUpdate()->first();
                $before = round((float) ($stock?->quantity ?? 0), 4);
                if (!$stock || $before < $quantity) {
                    throw ValidationException::withMessages(['items' => __('ui.workflow.material_issue_insufficient_stock', ['item' => $product->name, 'available' => number_format($before, 4, ',', '.')])]);
                }

                $lots = InventoryLot::query()->where('product_id', $product->id)
                    ->where('current_quantity', '>', 0)->orderBy('received_at')->orderBy('id')
                    ->lockForUpdate()->get();
                $lotBalance = $lots->sum(fn (InventoryLot $lot) => (float) $lot->current_quantity);
                if ($lotBalance + 0.0000001 < $quantity) {
                    throw ValidationException::withMessages(['items' => __('ui.workflow.lot_stock_balance_mismatch', ['item' => $product->name])]);
                }
                $lotAllocations = [];
                $remainingLotQuantity = $quantity;
                foreach ($lots as $lot) {
                    if ($remainingLotQuantity <= 0) break;
                    $allocated = min((float) $lot->current_quantity, $remainingLotQuantity);
                    if ($allocated <= 0) continue;
                    $lot->update(['current_quantity' => round((float) $lot->current_quantity - $allocated, 4)]);
                    $lotAllocations[] = ['inventory_lot_id' => $lot->id, 'quantity' => -$allocated, 'unit_cost' => $lot->unit_cost];
                    $remainingLotQuantity = round($remainingLotQuantity - $allocated, 4);
                }

                if ($request->production_order_id) {
                    $orderMaterials = ProductionOrderMaterial::query()
                        ->where('production_order_id', $request->production_order_id)
                        ->where('product_id', $product->id)->orderBy('id')->lockForUpdate()->get();
                    $orderRemaining = $orderMaterials->sum(fn ($material) => max(0, (float) $material->required_quantity - (float) $material->issued_quantity));
                    if ($orderRemaining + 0.0000001 < $quantity) {
                        throw ValidationException::withMessages(['items' => __('ui.workflow.production_order_material_limit')]);
                    }
                    $toAllocate = $quantity;
                    foreach ($orderMaterials as $orderMaterial) {
                        $available = max(0, (float) $orderMaterial->required_quantity - (float) $orderMaterial->issued_quantity);
                        $allocated = min($available, $toAllocate);
                        if ($allocated > 0) {
                            $orderMaterial->update(['issued_quantity' => round((float) $orderMaterial->issued_quantity + $allocated, 4)]);
                            $toAllocate = round($toAllocate - $allocated, 4);
                        }
                        if ($toAllocate <= 0) break;
                    }
                }

                $after = round($before - $quantity, 4);
                $unitCost = (float) $stock->average_unit_cost > 0 ? (float) $stock->average_unit_cost : (float) $product->purchase_price;
                $cost = round($quantity * $unitCost, 2);
                $stock->update(['quantity' => $after]);
                $item->update(['issued_quantity' => round((float) $item->issued_quantity + $quantity, 4)]);
                $movement = StockMovement::create([
                    'product_id' => $product->id,
                    'material_request_item_id' => $item->id,
                    'created_by' => $userId,
                    'movement_type' => 'material_issue',
                    'quantity' => -$quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'cost_amount' => $cost,
                    'notes' => __('ui.workflow.material_issue_reference', ['number' => $request->number]),
                ]);
                $movement->lotAllocations()->createMany($lotAllocations);
            }

            $this->auditLog->log(
                action: 'ISSUE', module: 'MATERIAL_REQUEST',
                description: __('ui.workflow.material_issue_audit', ['number' => $request->number]),
                model: $request,
                oldValues: null,
                newValues: ['issued_items' => $lines],
            );

            return $request->load(['items.product.unit']);
        });
    }
}

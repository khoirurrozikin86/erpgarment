<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Companies\Services\CompanyContext;
use App\Models\{InventoryLot, MaterialIssueItem, ProductStock, RawMaterialOrderItem, StockMovement};
use Illuminate\Validation\ValidationException;

class RestoreMaterialIssueItemAction
{
    public function __construct(private CompanyContext $company) {}

    /** Restore a source OBB issue while preserving both stock movement records. */
    public function __invoke(MaterialIssueItem $issueItem, int $userId): array
    {
        $item = MaterialIssueItem::query()->with('document')->lockForUpdate()->findOrFail($issueItem->id);
        if (!$item->raw_material_order_item_id || (int) $item->document->company_id !== $this->company->id()) {
            abort(404);
        }

        $source = RawMaterialOrderItem::query()->where('company_id', $this->company->id())
            ->lockForUpdate()->findOrFail($item->raw_material_order_item_id);
        if ((int) $source->product_id !== (int) $item->product_id) {
            throw ValidationException::withMessages(['item' => __('ui.material_issue.correction_source_mismatch')]);
        }

        $quantity = round((float) $item->quantity, 4);
        $movements = StockMovement::query()->where('material_issue_item_id', $item->id)
            ->with('lotAllocations')->lockForUpdate()->get();
        if ($movements->count() !== 1) {
            throw ValidationException::withMessages(['item' => __('ui.material_issue.correction_movement_missing')]);
        }
        /** @var StockMovement $movement */
        $movement = $movements->first();
        $allocated = $movement->lotAllocations->sum(fn ($allocation) => abs((float) $allocation->quantity));
        if ((float) $movement->quantity >= 0 || abs(abs((float) $movement->quantity) - $quantity) > 0.0001 || abs($allocated - $quantity) > 0.0001) {
            throw ValidationException::withMessages(['item' => __('ui.material_issue.correction_movement_mismatch')]);
        }

        $orderMaterial = $source->sourceMaterial()->lockForUpdate()->first();
        if (!$orderMaterial || (int) $orderMaterial->product_id !== (int) $item->product_id) {
            throw ValidationException::withMessages(['item' => __('ui.material_issue.source_material_missing')]);
        }
        $nextOrderIssued = round((float) $orderMaterial->issued_quantity - $quantity, 4);
        if ($nextOrderIssued + 0.0000001 < (float) $orderMaterial->consumed_quantity) {
            throw ValidationException::withMessages(['quantity' => __('ui.material_issue.correction_already_consumed')]);
        }
        if ((float) $source->issued_quantity + 0.0000001 < $quantity) {
            throw ValidationException::withMessages(['quantity' => __('ui.material_issue.correction_source_mismatch')]);
        }

        $stock = ProductStock::query()->where('product_id', $item->product_id)->lockForUpdate()->firstOrFail();
        $before = round((float) $stock->quantity, 4);
        $after = round($before + $quantity, 4);
        $restoredAllocations = [];
        foreach ($movement->lotAllocations as $allocation) {
            if ((float) $allocation->quantity >= 0) {
                throw ValidationException::withMessages(['item' => __('ui.material_issue.correction_movement_mismatch')]);
            }
            $lot = InventoryLot::query()->lockForUpdate()->find($allocation->inventory_lot_id);
            if (!$lot) throw ValidationException::withMessages(['item' => __('ui.material_issue.correction_lot_missing')]);
            $lotQuantity = abs((float) $allocation->quantity);
            $lot->update(['current_quantity' => round((float) $lot->current_quantity + $lotQuantity, 4)]);
            $restoredAllocations[] = [
                'inventory_lot_id' => $lot->id,
                'quantity' => $lotQuantity,
                'unit_cost' => $allocation->unit_cost,
            ];
        }

        $stock->update(['quantity' => $after]);
        $source->update(['issued_quantity' => round((float) $source->issued_quantity - $quantity, 4)]);
        $orderMaterial->update(['issued_quantity' => $nextOrderIssued]);

        $number = $item->document->number;
        $movement->update([
            'material_issue_document_id' => null,
            'material_issue_item_id' => null,
            'notes' => trim(($movement->notes ?: '') . ' · ' . __('ui.material_issue.original_issue_reference', ['number' => $number])),
        ]);
        $reversal = StockMovement::query()->create([
            'product_id' => $item->product_id,
            'created_by' => $userId,
            'movement_type' => 'material_issue_reversal',
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'cost_amount' => $movement->cost_amount,
            'notes' => __('ui.material_issue.reversal_note', ['number' => $number]),
        ]);
        $reversal->lotAllocations()->createMany($restoredAllocations);

        return [$item, $source, $orderMaterial, $stock];
    }
}

<?php

namespace App\Domain\Inventory\Services;

use App\Models\InventoryLot;
use App\Models\StockMovement;
use Illuminate\Validation\ValidationException;

class StockMovementLotAllocator
{
    public function allocate(StockMovement $movement): void
    {
        // These inventory actions already write explicit lot allocations.
        if (in_array($movement->movement_type, ['purchase_receipt', 'purchase_return', 'material_issue', 'material_issue_reversal', 'production_output'], true)) {
            return;
        }

        $quantity = abs((float) $movement->quantity);
        if ($quantity <= 0) return;

        if ((float) $movement->quantity < 0) {
            $this->consumeOldestLots($movement, $quantity);
            return;
        }

        $sourceType = match ($movement->movement_type) {
            'customer_return' => 'customer_return',
            'stock_adjustment', 'damaged_item_reversal', 'lost_item_reversal' => 'adjustment',
            default => 'stock_in',
        };
        $lot = InventoryLot::create([
            'product_id' => $movement->product_id,
            'lot_number' => strtoupper(substr($sourceType, 0, 5)) . '-' . $movement->id,
            'received_at' => $movement->created_at ?? now(),
            'initial_quantity' => $quantity,
            'current_quantity' => $quantity,
            'unit_cost' => $movement->cost_amount > 0 ? round((float) $movement->cost_amount / $quantity, 6) : 0,
            'source_type' => $sourceType,
        ]);
        $movement->lotAllocations()->create([
            'inventory_lot_id' => $lot->id,
            'quantity' => $quantity,
            'unit_cost' => $lot->unit_cost,
        ]);
    }

    private function consumeOldestLots(StockMovement $movement, float $quantity): void
    {
        $lots = InventoryLot::query()->where('product_id', $movement->product_id)
            ->where('current_quantity', '>', 0)
            ->orderBy('received_at')->orderBy('id')->lockForUpdate()->get();
        $available = $lots->sum(fn (InventoryLot $lot) => (float) $lot->current_quantity);
        if ($available + 0.0000001 < $quantity) {
            throw ValidationException::withMessages([
                'stock' => __('ui.workflow.lot_stock_balance_mismatch', ['item' => $movement->product?->name ?? $movement->product_id]),
            ]);
        }

        $remaining = $quantity;
        $allocations = [];
        foreach ($lots as $lot) {
            if ($remaining <= 0) break;
            $allocated = min((float) $lot->current_quantity, $remaining);
            if ($allocated <= 0) continue;
            $lot->update(['current_quantity' => round((float) $lot->current_quantity - $allocated, 4)]);
            $allocations[] = [
                'inventory_lot_id' => $lot->id,
                'quantity' => -$allocated,
                'unit_cost' => $lot->unit_cost,
            ];
            $remaining = round($remaining - $allocated, 4);
        }
        $movement->lotAllocations()->createMany($allocations);
    }
}

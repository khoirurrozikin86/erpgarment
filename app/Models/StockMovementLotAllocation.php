<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovementLotAllocation extends Model
{
    protected $fillable = ['stock_movement_id', 'inventory_lot_id', 'quantity', 'unit_cost'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4', 'unit_cost' => 'decimal:6'];
    }

    public function stockMovement(): BelongsTo { return $this->belongsTo(StockMovement::class); }
    public function inventoryLot(): BelongsTo { return $this->belongsTo(InventoryLot::class); }
}

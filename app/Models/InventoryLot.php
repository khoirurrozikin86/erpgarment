<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryLot extends Model
{
    protected $fillable = [
        'product_id', 'supplier_id', 'lot_number', 'received_at', 'initial_quantity',
        'current_quantity', 'unit_cost', 'source_type',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime', 'initial_quantity' => 'decimal:4',
            'current_quantity' => 'decimal:4', 'unit_cost' => 'decimal:6',
        ];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function receiptItems(): HasMany { return $this->hasMany(GoodsReceiptItem::class); }
    public function allocations(): HasMany { return $this->hasMany(StockMovementLotAllocation::class); }
}

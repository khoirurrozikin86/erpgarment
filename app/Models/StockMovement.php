<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'goods_receipt_id',
        'goods_receipt_item_id',
        'purchase_return_id',
        'purchase_return_item_id',
        'stock_opname_id',
        'stock_opname_item_id',
        'material_request_item_id',
        'production_order_stage_entry_id',
        'created_by',
        'movement_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'cost_amount',
        'notes',
    ];

    protected static function booted(): void
    {
        static::created(function (StockMovement $movement) {
            app(\App\Domain\Inventory\Services\StockMovementLotAllocator::class)->allocate($movement);
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'quantity_before' => 'decimal:4',
            'quantity_after' => 'decimal:4',
            'cost_amount' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }


    public function materialRequestItem(): BelongsTo
    {
        return $this->belongsTo(MaterialRequestItem::class);
    }

    public function productionOrderStageEntry(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderStageEntry::class);
    }

    public function lotAllocations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockMovementLotAllocation::class);
    }
}

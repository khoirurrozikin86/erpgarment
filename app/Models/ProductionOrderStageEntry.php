<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionOrderStageEntry extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'production_order_id', 'stage', 'input_quantity', 'good_quantity',
        'rejected_quantity', 'waste_quantity', 'notes', 'processed_by', 'processed_at',
        'cutting_number', 'sewing_number', 'packing_number', 'finished_goods_number', 'material_product_id', 'material_waste_quantity',
    ];

    protected function casts(): array
    {
        return [
            'input_quantity' => 'decimal:4', 'good_quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4', 'waste_quantity' => 'decimal:4',
            'processed_at' => 'datetime',
            'material_waste_quantity' => 'decimal:4',
        ];
    }

    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
    public function processor(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
    public function materialProduct(): BelongsTo { return $this->belongsTo(Product::class, 'material_product_id'); }
}

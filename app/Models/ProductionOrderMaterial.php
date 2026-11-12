<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderMaterial extends Model
{
    protected $fillable = [
        'production_order_id', 'product_id', 'operation', 'quantity_per_output',
        'wastage_percent', 'required_quantity', 'issued_quantity', 'consumed_quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity_per_output' => 'decimal:4', 'wastage_percent' => 'decimal:3',
            'required_quantity' => 'decimal:4', 'issued_quantity' => 'decimal:4',
            'consumed_quantity' => 'decimal:4',
        ];
    }

    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}

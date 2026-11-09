<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    protected $fillable = ['product_id', 'quantity', 'average_unit_cost'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4', 'average_unit_cost' => 'decimal:6'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

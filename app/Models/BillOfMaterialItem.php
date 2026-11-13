<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillOfMaterialItem extends Model
{
    protected $fillable = ['bill_of_material_id', 'product_id', 'quantity', 'span', 'wastage_percent', 'operation', 'notes'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4', 'span' => 'decimal:4', 'wastage_percent' => 'decimal:3'];
    }

    public function billOfMaterial(): BelongsTo { return $this->belongsTo(BillOfMaterial::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}

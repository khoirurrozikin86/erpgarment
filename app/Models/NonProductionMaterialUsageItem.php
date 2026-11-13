<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NonProductionMaterialUsageItem extends Model
{
    use SoftDeletes;

    protected $fillable = ['document_id', 'product_id', 'quantity', 'unit_price', 'total_price'];
    protected function casts(): array { return ['quantity' => 'decimal:4', 'unit_price' => 'decimal:4', 'total_price' => 'decimal:4']; }
    public function document(): BelongsTo { return $this->belongsTo(NonProductionMaterialUsageDocument::class, 'document_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}

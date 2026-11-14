<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionReturnItem extends Model
{
    use SoftDeletes;
    protected $fillable = ['production_return_document_id', 'product_id', 'quantity', 'description'];
    protected function casts(): array { return ['quantity' => 'decimal:4']; }
    public function document(): BelongsTo { return $this->belongsTo(ProductionReturnDocument::class, 'production_return_document_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}

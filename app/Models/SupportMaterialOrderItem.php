<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportMaterialOrderItem extends Model
{
    use SoftDeletes;
    protected $fillable = ['document_id', 'product_id', 'quantity', 'po_other', 'style'];
    protected function casts(): array { return ['quantity' => 'decimal:4']; }
    public function document(): BelongsTo { return $this->belongsTo(SupportMaterialOrderDocument::class, 'document_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}

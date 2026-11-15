<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubcontractSemiFinishedItem extends Model
{
    protected $fillable = ['document_id', 'product_id', 'quantity'];
    protected function casts(): array { return ['quantity' => 'decimal:4']; }
    public function document(): BelongsTo { return $this->belongsTo(SubcontractSemiFinishedDocument::class, 'document_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}

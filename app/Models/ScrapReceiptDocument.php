<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScrapReceiptDocument extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'number', 'receipt_date', 'scrap_product_id', 'source_product_id', 'production_number', 'quantity', 'description', 'created_by'];

    protected function casts(): array { return ['receipt_date' => 'date', 'quantity' => 'decimal:4']; }

    public function scrapProduct(): BelongsTo { return $this->belongsTo(Product::class, 'scrap_product_id'); }
    public function sourceProduct(): BelongsTo { return $this->belongsTo(Product::class, 'source_product_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}

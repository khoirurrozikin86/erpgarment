<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesQuotationItem extends Model
{
    protected $fillable = [
        'product_id', 'quantity', 'unit_price', 'discount_amount', 'tax_rate', 'line_total', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4', 'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2', 'tax_rate' => 'decimal:2', 'line_total' => 'decimal:2',
        ];
    }

    public function quotation(): BelongsTo { return $this->belongsTo(SalesQuotation::class, 'sales_quotation_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}

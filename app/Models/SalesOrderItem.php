<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    protected $fillable = [
        'sales_quotation_item_id', 'product_id', 'quantity', 'delivered_quantity', 'unit_price',
        'discount_amount', 'tax_rate', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4', 'delivered_quantity' => 'decimal:4', 'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2', 'tax_rate' => 'decimal:2', 'line_total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(SalesOrder::class, 'sales_order_id'); }
    public function quotationItem(): BelongsTo { return $this->belongsTo(SalesQuotationItem::class, 'sales_quotation_item_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function deliveries(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(DeliveryItem::class); }
}

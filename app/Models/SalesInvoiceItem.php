<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoiceItem extends Model
{
    protected $fillable = [
        'delivery_item_id',
        'sales_order_item_id',
        'product_id',
        'quantity',
        'unit_price',
        'returned_quantity',
        'discount_amount',
        'tax_rate',
        'line_total',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4', 'returned_quantity' => 'decimal:4', 'unit_price' => 'decimal:2', 'discount_amount' => 'decimal:2', 'tax_rate' => 'decimal:2', 'line_total' => 'decimal:2'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }
    public function deliveryItem(): BelongsTo
    {
        return $this->belongsTo(DeliveryItem::class);
    }
    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function customerReturnItems(): HasMany
    {
        return $this->hasMany(CustomerReturnItem::class);
    }
}

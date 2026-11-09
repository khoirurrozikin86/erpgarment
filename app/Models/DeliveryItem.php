<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryItem extends Model
{
    protected $fillable = ['sales_order_item_id', 'product_id', 'quantity'];

    protected function casts(): array { return ['quantity' => 'decimal:4']; }

    public function delivery(): BelongsTo { return $this->belongsTo(Delivery::class); }
    public function salesOrderItem(): BelongsTo { return $this->belongsTo(SalesOrderItem::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}

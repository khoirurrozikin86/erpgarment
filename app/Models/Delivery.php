<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Delivery extends Model
{
    protected $fillable = ['number', 'sales_order_id', 'customer_id', 'delivered_by', 'delivered_at', 'status', 'notes', 'posted_at'];

    protected function casts(): array { return ['delivered_at' => 'datetime', 'posted_at' => 'datetime']; }

    public function salesOrder(): BelongsTo { return $this->belongsTo(SalesOrder::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function deliverer(): BelongsTo { return $this->belongsTo(User::class, 'delivered_by'); }
    public function items(): HasMany { return $this->hasMany(DeliveryItem::class); }
    public function salesInvoice(): HasOne { return $this->hasOne(SalesInvoice::class); }
}

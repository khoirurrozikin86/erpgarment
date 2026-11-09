<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    protected $fillable = [
        'number', 'sales_quotation_id', 'customer_id', 'created_by', 'confirmed_by', 'order_date',
        'requested_delivery_at', 'subtotal', 'tax_amount', 'total_amount', 'status', 'notes', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date', 'requested_delivery_at' => 'date', 'confirmed_at' => 'datetime',
            'subtotal' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total_amount' => 'decimal:2',
        ];
    }

    public function quotation(): BelongsTo { return $this->belongsTo(SalesQuotation::class, 'sales_quotation_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function confirmer(): BelongsTo { return $this->belongsTo(User::class, 'confirmed_by'); }
    public function items(): HasMany { return $this->hasMany(SalesOrderItem::class); }
    public function deliveries(): HasMany { return $this->hasMany(Delivery::class); }
}

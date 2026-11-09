<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    protected $fillable = [
        'number',
        'delivery_id',
        'sales_order_id',
        'customer_id',
        'created_by',
        'issued_by',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'notes',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'issued_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }
    public function customerReturns(): HasMany
    {
        return $this->hasMany(CustomerReturn::class);
    }
    public function emailLogs(): HasMany
    {
        return $this->hasMany(SalesInvoiceEmailLog::class)->latest('sent_at');
    }
    public function payments(): HasMany
    {
        return $this->hasMany(SalesInvoicePayment::class)->orderByDesc('payment_date')->orderByDesc('id');
    }
}

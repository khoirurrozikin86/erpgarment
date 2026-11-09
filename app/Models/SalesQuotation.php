<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesQuotation extends Model
{
    protected $fillable = [
        'number', 'customer_id', 'created_by', 'issued_by', 'reviewed_by', 'quote_date', 'valid_until',
        'subtotal', 'tax_amount', 'total_amount', 'status', 'notes', 'review_note', 'issued_at', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'quote_date' => 'date', 'valid_until' => 'date',
            'subtotal' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total_amount' => 'decimal:2',
            'issued_at' => 'datetime', 'reviewed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function issuer(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function items(): HasMany { return $this->hasMany(SalesQuotationItem::class); }
    public function emailLogs(): HasMany { return $this->hasMany(SalesQuotationEmailLog::class)->latest('sent_at'); }
    public function salesOrder(): HasOne { return $this->hasOne(SalesOrder::class); }
}

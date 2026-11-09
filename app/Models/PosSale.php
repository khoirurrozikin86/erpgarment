<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends Model
{
    protected $fillable = ['company_id', 'number', 'pos_session_id', 'customer_id', 'cashier_id', 'cash_bank_account_id', 'sold_at', 'subtotal', 'discount_rate', 'discount_amount', 'tax_amount', 'total_amount', 'payment_method', 'paid_amount', 'change_amount', 'status', 'voided_by', 'voided_at', 'void_reason'];

    protected function casts(): array
    {
        return ['sold_at' => 'datetime', 'voided_at' => 'datetime', 'subtotal' => 'decimal:2', 'discount_rate' => 'decimal:2', 'discount_amount' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total_amount' => 'decimal:2', 'paid_amount' => 'decimal:2', 'change_amount' => 'decimal:2'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
    public function cashBankAccount(): BelongsTo
    {
        return $this->belongsTo(CashBankAccount::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class);
    }
}

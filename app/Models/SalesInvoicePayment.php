<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoicePayment extends Model
{
    protected $fillable = [
        'number', 'sales_invoice_id', 'cash_bank_account_id', 'received_by', 'payment_date', 'amount', 'payment_method', 'reference_number', 'notes',
    ];

    protected function casts(): array
    {
        return ['payment_date' => 'date', 'amount' => 'decimal:2'];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id'); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function cashBankAccount(): BelongsTo { return $this->belongsTo(CashBankAccount::class); }
}

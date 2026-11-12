<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashBankTransaction extends Model
{
    protected $fillable = ['company_id', 'cash_bank_account_id', 'counter_chart_account_id', 'supplier_payment_id', 'transaction_date', 'direction', 'amount', 'description', 'reference_number', 'created_by'];

    protected function casts(): array
    {
        return ['transaction_date' => 'date', 'amount' => 'decimal:2'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CashBankAccount::class, 'cash_bank_account_id');
    }
    public function counterAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'counter_chart_account_id');
    }
    public function supplierPayment(): BelongsTo
    {
        return $this->belongsTo(SupplierPayment::class);
    }
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

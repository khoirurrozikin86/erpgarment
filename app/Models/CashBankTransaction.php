<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashBankTransaction extends Model
{
    protected $fillable = ['company_id', 'cash_bank_account_id', 'counter_chart_account_id', 'sales_invoice_payment_id', 'supplier_payment_id', 'customer_return_id', 'pos_session_id', 'pos_sale_id', 'pos_sale_void_id', 'transaction_date', 'direction', 'amount', 'description', 'reference_number', 'created_by'];

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
    public function payment(): BelongsTo
    {
        return $this->belongsTo(SalesInvoicePayment::class, 'sales_invoice_payment_id');
    }
    public function supplierPayment(): BelongsTo
    {
        return $this->belongsTo(SupplierPayment::class);
    }
    public function customerReturn(): BelongsTo
    {
        return $this->belongsTo(CustomerReturn::class);
    }
    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }
    public function posSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class);
    }
    public function posSaleVoid(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_void_id');
    }
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

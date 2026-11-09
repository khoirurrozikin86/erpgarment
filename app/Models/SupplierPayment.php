<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    protected $fillable = ['company_id', 'number', 'goods_receipt_id', 'supplier_id', 'cash_bank_account_id', 'paid_by', 'payment_date', 'amount', 'reference_number', 'notes'];

    protected function casts(): array
    {
        return ['payment_date' => 'date', 'amount' => 'decimal:2'];
    }

    public function goodsReceipt(): BelongsTo { return $this->belongsTo(GoodsReceipt::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function cashBankAccount(): BelongsTo { return $this->belongsTo(CashBankAccount::class); }
    public function payer(): BelongsTo { return $this->belongsTo(User::class, 'paid_by'); }
}

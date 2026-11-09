<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSession extends Model
{
    protected $fillable = ['company_id', 'number', 'cash_bank_account_id', 'opened_by', 'closed_by', 'opened_at', 'closed_at', 'opening_cash', 'expected_cash', 'counted_cash', 'cash_difference', 'status', 'notes'];

    protected function casts(): array
    {
        return ['opened_at' => 'datetime', 'closed_at' => 'datetime', 'opening_cash' => 'decimal:2', 'expected_cash' => 'decimal:2', 'counted_cash' => 'decimal:2', 'cash_difference' => 'decimal:2'];
    }

    public function cashBankAccount(): BelongsTo { return $this->belongsTo(CashBankAccount::class); }
    public function opener(): BelongsTo { return $this->belongsTo(User::class, 'opened_by'); }
    public function closer(): BelongsTo { return $this->belongsTo(User::class, 'closed_by'); }
    public function cashBankTransactions(): HasMany { return $this->hasMany(CashBankTransaction::class); }
    public function sales(): HasMany { return $this->hasMany(PosSale::class); }
}

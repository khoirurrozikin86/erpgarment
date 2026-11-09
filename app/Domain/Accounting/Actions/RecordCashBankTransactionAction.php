<?php

namespace App\Domain\Accounting\Actions;

use App\Domain\Accounting\DTOs\CashBankTransactionData;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\CashBankAccount;
use App\Models\CashBankTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordCashBankTransactionAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $auditLog, private AutomaticJournalService $journals) {}

    public function __invoke(CashBankTransactionData $data, int $userId): CashBankTransaction
    {
        return DB::transaction(function () use ($data, $userId) {
            $attributes = $data->attributes;
            $account = CashBankAccount::query()->where('company_id', $this->company->id())
                ->where('is_active', true)->lockForUpdate()->find($attributes['cash_bank_account_id']);
            if (!$account) {
                throw ValidationException::withMessages(['cash_bank_account_id' => 'Pilih akun kas/bank aktif dari perusahaan ini.']);
            }
            $counterAccount = \App\Models\ChartOfAccount::query()->where('company_id', $this->company->id())
                ->where('is_active', true)->where('is_group', false)->find($attributes['counter_chart_account_id'] ?? null);
            if (!$counterAccount || $counterAccount->id === $account->chart_of_account_id) {
                throw ValidationException::withMessages(['counter_chart_account_id' => 'Pilih akun lawan COA transaksi yang aktif dan berbeda dari akun kas/bank.']);
            }

            $transaction = CashBankTransaction::create([
                'company_id' => $this->company->id(),
                'cash_bank_account_id' => $account->id,
                'counter_chart_account_id' => $counterAccount->id,
                'transaction_date' => $attributes['transaction_date'],
                'direction' => $attributes['direction'],
                'amount' => $attributes['amount'],
                'description' => $attributes['description'],
                'reference_number' => $attributes['reference_number'] ?? null,
                'created_by' => $userId,
            ]);
            $this->auditLog->log(
                action: 'CREATE', module: 'CASH_BANK_TRANSACTION',
                description: "Mencatat transaksi {$attributes['direction']} {$transaction->amount} pada akun {$account->code}",
                oldValues: null, newValues: $transaction->toArray(),
            );
            $this->journals->recordManualCashBankTransaction($transaction, $userId);
            return $transaction->load('account');
        });
    }
}

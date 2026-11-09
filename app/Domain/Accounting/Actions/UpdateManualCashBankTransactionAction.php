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

class UpdateManualCashBankTransactionAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $auditLog, private AutomaticJournalService $journals) {}

    public function __invoke(CashBankTransaction $transaction, CashBankTransactionData $data, int $userId): CashBankTransaction
    {
        return DB::transaction(function () use ($transaction, $data, $userId) {
            $transaction = CashBankTransaction::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($transaction->id);
            if ($transaction->sales_invoice_payment_id || $transaction->supplier_payment_id || $transaction->customer_return_id || $transaction->pos_sale_void_id || $transaction->pos_session_id || $transaction->pos_sale_id) {
                throw ValidationException::withMessages(['transaction' => 'Mutasi otomatis dari Account Receivable/Payable/POS/Customer Return dikelola melalui sumber transaksinya.']);
            }

            $account = CashBankAccount::query()->where('company_id', $this->company->id())->where('is_active', true)->find($data->attributes['cash_bank_account_id']);
            if (!$account) {
                throw ValidationException::withMessages(['cash_bank_account_id' => 'Pilih akun kas/bank aktif dari perusahaan ini.']);
            }
            $counterAccount = \App\Models\ChartOfAccount::query()->where('company_id', $this->company->id())
                ->where('is_active', true)->where('is_group', false)->find($data->attributes['counter_chart_account_id'] ?? null);
            if (!$counterAccount || $counterAccount->id === $account->chart_of_account_id) {
                throw ValidationException::withMessages(['counter_chart_account_id' => 'Pilih akun lawan COA transaksi yang aktif dan berbeda dari akun kas/bank.']);
            }

            $oldValues = $transaction->toArray();
            $transaction->fill([
                'cash_bank_account_id' => $account->id,
                'counter_chart_account_id' => $counterAccount->id,
                'transaction_date' => $data->attributes['transaction_date'],
                'direction' => $data->attributes['direction'],
                'amount' => $data->attributes['amount'],
                'description' => $data->attributes['description'],
                'reference_number' => $data->attributes['reference_number'] ?? null,
            ])->save();

            $this->auditLog->log(action: 'UPDATE', module: 'CASH_BANK_TRANSACTION', description: "Memperbarui mutasi kas/bank {$transaction->id}", model: $transaction, oldValues: $oldValues, newValues: $transaction->fresh()->toArray());
            $this->journals->reviseManualCashBankTransaction($transaction->fresh(['account.chartOfAccount', 'counterAccount']), $userId);
            return $transaction->fresh('account');
        });
    }
}

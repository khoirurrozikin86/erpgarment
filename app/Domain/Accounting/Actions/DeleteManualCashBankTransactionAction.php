<?php

namespace App\Domain\Accounting\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\CashBankTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteManualCashBankTransactionAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $auditLog, private AutomaticJournalService $journals) {}

    public function __invoke(CashBankTransaction $transaction, int $userId): void
    {
        DB::transaction(function () use ($transaction, $userId) {
            $transaction = CashBankTransaction::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($transaction->id);
            if ($transaction->sales_invoice_payment_id || $transaction->supplier_payment_id || $transaction->customer_return_id || $transaction->pos_sale_void_id || $transaction->pos_session_id || $transaction->pos_sale_id) {
                throw ValidationException::withMessages(['transaction' => 'Mutasi otomatis dari Account Receivable/Payable/POS/Customer Return tidak dapat dihapus dari halaman Cash & Bank.']);
            }

            $oldValues = $transaction->toArray();
            $this->journals->reverseManualCashBankTransaction($transaction, $userId);
            $this->auditLog->log(action: 'DELETE', module: 'CASH_BANK_TRANSACTION', description: "Menghapus mutasi kas/bank {$transaction->id}", model: $transaction, oldValues: $oldValues, newValues: null);
            $transaction->delete();
        });
    }
}

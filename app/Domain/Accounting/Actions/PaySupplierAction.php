<?php

namespace App\Domain\Accounting\Actions;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\CashBankAccount;
use App\Models\CashBankTransaction;
use App\Models\ChartOfAccount;
use App\Models\GoodsReceipt;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaySupplierAction
{
    public function __construct(
        private CompanyContext $company,
        private DocumentNumberService $numbers,
        private AuditLogService $auditLog,
        private AutomaticJournalService $journals,
    ) {}

    public function __invoke(GoodsReceipt $receipt, array $data, int $userId): SupplierPayment
    {
        return DB::transaction(function () use ($receipt, $data, $userId) {
            $receipt = GoodsReceipt::query()->lockForUpdate()->findOrFail($receipt->id);
            $receiptAmount = (float) $receipt->items()->sum('line_total');
            $returned = (float) $receipt->purchaseReturns()->sum('total_amount');
            $paid = (float) $receipt->supplierPayments()->where('company_id', $this->company->id())->sum('amount');
            $outstanding = max(0, round($receiptAmount - $returned - $paid, 2));
            $amount = round((float) $data['amount'], 2);
            if ($outstanding <= 0 || $amount > $outstanding) {
                throw ValidationException::withMessages(['amount' => 'Jumlah pembayaran melebihi sisa utang pada penerimaan barang.']);
            }

            $account = CashBankAccount::query()->where('company_id', $this->company->id())
                ->where('is_active', true)->lockForUpdate()->find($data['cash_bank_account_id']);
            if (!$account || !$account->chart_of_account_id) {
                throw ValidationException::withMessages(['cash_bank_account_id' => 'Pilih akun kas/bank aktif yang sudah dipetakan ke COA.']);
            }
            $payable = ChartOfAccount::query()->where('company_id', $this->company->id())->where('code', '2130')
                ->where('is_active', true)->where('is_group', false)->first();
            if (!$payable) {
                throw ValidationException::withMessages(['accounting' => 'Akun Barang Diterima Belum Ditagih (2130) tidak ditemukan. Jalankan ChartOfAccountsSeeder.']);
            }

            $payment = SupplierPayment::create([
                'company_id' => $this->company->id(),
                'number' => $this->numbers->generate('supplier_payment'),
                'goods_receipt_id' => $receipt->id,
                'supplier_id' => $receipt->supplier_id,
                'cash_bank_account_id' => $account->id,
                'paid_by' => $userId,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $transaction = CashBankTransaction::create([
                'company_id' => $this->company->id(),
                'cash_bank_account_id' => $account->id,
                'counter_chart_account_id' => $payable->id,
                'supplier_payment_id' => $payment->id,
                'transaction_date' => $payment->payment_date,
                'direction' => 'out',
                'amount' => $amount,
                'description' => "Pembayaran utang supplier untuk penerimaan {$receipt->number}",
                'reference_number' => $payment->reference_number,
                'created_by' => $userId,
            ]);
            $this->journals->recordManualCashBankTransaction($transaction, $userId);
            $this->auditLog->log(action: 'PAY', module: 'ACCOUNTS_PAYABLE', description: "Mencatat pembayaran {$payment->number} untuk penerimaan {$receipt->number}", model: $payment, oldValues: ['outstanding' => $outstanding], newValues: ['payment' => $payment->toArray(), 'outstanding' => round($outstanding - $amount, 2)]);
            return $payment->load('payer');
        });
    }
}

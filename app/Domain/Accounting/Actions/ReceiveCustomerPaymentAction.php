<?php

namespace App\Domain\Accounting\Actions;

use App\Domain\Accounting\DTOs\ReceiveCustomerPaymentData;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Models\CashBankAccount;
use App\Models\CashBankTransaction;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiveCustomerPaymentAction
{
    public function __construct(private DocumentNumberService $numbers, private AuditLogService $auditLog, private CompanyContext $company, private AutomaticJournalService $journals) {}

    public function __invoke(SalesInvoice $invoice, ReceiveCustomerPaymentData $data, int $userId)
    {
        return DB::transaction(function () use ($invoice, $data, $userId) {
            $invoice = SalesInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($invoice->status !== 'issued') {
                throw ValidationException::withMessages(['invoice' => 'Pembayaran hanya dapat dicatat untuk Sales Invoice yang sudah diterbitkan.']);
            }

            $paid = (float) $invoice->payments()->sum('amount');
            $outstanding = max(0, round((float) $invoice->total_amount - $paid, 2));
            $amount = round((float) $data->attributes['amount'], 2);
            if ($outstanding <= 0) {
                throw ValidationException::withMessages(['invoice' => 'Sales Invoice ini sudah lunas.']);
            }
            if ($amount > $outstanding) {
                throw ValidationException::withMessages(['amount' => "Jumlah pembayaran melebihi sisa piutang Rp " . number_format($outstanding, 2, ',', '.') . '.']);
            }

            $account = CashBankAccount::query()->where('company_id', $this->company->id())
                ->where('is_active', true)->lockForUpdate()->find($data->attributes['cash_bank_account_id']);
            if (!$account) {
                throw ValidationException::withMessages(['cash_bank_account_id' => 'Pilih akun kas/bank aktif dari perusahaan ini.']);
            }
            $expectedType = $data->attributes['payment_method'] === 'cash' ? 'cash' : ($data->attributes['payment_method'] === 'other' ? null : 'bank');
            if ($expectedType && $account->type !== $expectedType) {
                throw ValidationException::withMessages(['cash_bank_account_id' => $expectedType === 'cash' ? 'Metode tunai harus memakai akun kas.' : 'Metode transfer/kartu harus memakai akun bank.']);
            }

            $payment = $invoice->payments()->create([
                'number' => $this->numbers->generate('customer_payment'),
                'cash_bank_account_id' => $account->id,
                'received_by' => $userId,
                'payment_date' => $data->attributes['payment_date'],
                'amount' => $amount,
                'payment_method' => $data->attributes['payment_method'],
                'reference_number' => $data->attributes['reference_number'] ?? null,
                'notes' => $data->attributes['notes'] ?? null,
            ]);

            CashBankTransaction::create([
                'company_id' => $this->company->id(),
                'cash_bank_account_id' => $account->id,
                'sales_invoice_payment_id' => $payment->id,
                'transaction_date' => $payment->payment_date,
                'direction' => 'in',
                'amount' => $amount,
                'description' => "Penerimaan pembayaran invoice {$invoice->number}",
                'reference_number' => $payment->reference_number,
                'created_by' => $userId,
            ]);
            $this->journals->recordCustomerPayment($payment, $userId);

            $this->auditLog->log(
                action: 'RECEIVE', module: 'ACCOUNTS_RECEIVABLE',
                description: "Mencatat pembayaran {$payment->number} untuk invoice {$invoice->number}",
                model: $payment, oldValues: ['outstanding' => $outstanding],
                newValues: ['payment' => $payment->toArray(), 'outstanding' => round($outstanding - $amount, 2)],
            );

            return $payment->load('receiver');
        });
    }
}

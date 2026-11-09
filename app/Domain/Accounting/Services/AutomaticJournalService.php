<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\CashBankAccount;
use App\Models\CashBankTransaction;
use App\Models\ChartOfAccount;
use App\Models\CustomerReturn;
use App\Models\JournalEntry;
use App\Models\SalesInvoice;
use App\Models\SalesInvoicePayment;
use App\Models\StockMovement;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseReturnItem;
use App\Models\PosSale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AutomaticJournalService
{
    public function __construct(private CompanyContext $company, private DocumentNumberService $numbers, private AuditLogService $auditLog) {}

    public function recordSalesInvoice(SalesInvoice $invoice, int $userId): JournalEntry
    {
        return DB::transaction(function () use ($invoice, $userId) {
            if ($entry = $this->existing(SalesInvoice::class, $invoice->id)) return $entry;
            $receivable = $this->account('1130', 'asset');
            $revenue = $this->account('4100', 'revenue');
            $tax = round((float) $invoice->tax_amount, 2);
            $subtotal = round((float) $invoice->subtotal, 2);
            $total = round((float) $invoice->total_amount, 2);
            if (round($subtotal + $tax, 2) !== $total) {
                throw ValidationException::withMessages(['accounting' => 'Subtotal dan pajak invoice tidak sama dengan total; jurnal tidak dibuat.']);
            }
            $lines = [
                ['chart_of_account_id' => $receivable->id, 'line_number' => 1, 'description' => "Piutang invoice {$invoice->number}", 'debit' => $total, 'credit' => 0],
                ['chart_of_account_id' => $revenue->id, 'line_number' => 2, 'description' => "Penjualan invoice {$invoice->number}", 'debit' => 0, 'credit' => $subtotal],
            ];
            if ($tax > 0) {
                $taxAccount = $this->account('2120', 'liability');
                $lines[] = ['chart_of_account_id' => $taxAccount->id, 'line_number' => 3, 'description' => "Pajak invoice {$invoice->number}", 'debit' => 0, 'credit' => $tax];
            }
            return $this->createPosted(
                sourceType: SalesInvoice::class,
                sourceId: $invoice->id,
                date: $invoice->invoice_date,
                reference: $invoice->number,
                description: "Penerbitan Sales Invoice {$invoice->number}",
                lines: $lines,
                total: $total,
                userId: $userId,
                sourceAction: 'original',
            );
        });
    }

    public function recordCustomerPayment(SalesInvoicePayment $payment, int $userId): JournalEntry
    {
        return DB::transaction(function () use ($payment, $userId) {
            if ($entry = $this->existing(SalesInvoicePayment::class, $payment->id)) return $entry;
            $payment->loadMissing(['cashBankAccount.chartOfAccount', 'invoice']);
            $bankAccount = $payment->cashBankAccount;
            $cashCoa = $bankAccount?->chartOfAccount;
            if (!$bankAccount || !$cashCoa || !$cashCoa->is_active || $cashCoa->is_group || $cashCoa->account_type !== 'asset') {
                throw ValidationException::withMessages(['accounting' => 'Akun kas/bank belum terhubung ke akun COA aset yang aktif. Perbarui pemetaan akun lalu coba kembali.']);
            }
            $receivable = $this->account('1130', 'asset');
            $amount = round((float) $payment->amount, 2);
            return $this->createPosted(
                sourceType: SalesInvoicePayment::class,
                sourceId: $payment->id,
                date: $payment->payment_date,
                reference: $payment->number,
                description: "Penerimaan pembayaran invoice {$payment->invoice?->number}",
                lines: [
                    ['chart_of_account_id' => $cashCoa->id, 'line_number' => 1, 'description' => "Penerimaan {$payment->number}", 'debit' => $amount, 'credit' => 0],
                    ['chart_of_account_id' => $receivable->id, 'line_number' => 2, 'description' => "Pelunasan piutang {$payment->invoice?->number}", 'debit' => 0, 'credit' => $amount],
                ],
                total: $amount,
                userId: $userId,
                sourceAction: 'original',
            );
        });
    }

    public function recordCustomerReturnRefund(CashBankTransaction $transaction, CustomerReturn $customerReturn, int $userId): JournalEntry
    {
        if ($entry = $this->existing(CashBankTransaction::class, $transaction->id)) return $entry;

        $transaction->loadMissing(['account.chartOfAccount']);
        $cashCoa = $transaction->account?->chartOfAccount;
        if (!$cashCoa || !$cashCoa->is_active || $cashCoa->is_group || $cashCoa->account_type !== 'asset' || $cashCoa->company_id !== $this->company->id()) {
            throw ValidationException::withMessages(['accounting' => 'Akun refund belum terhubung ke COA aset aktif.']);
        }

        $subtotal = round((float) $customerReturn->subtotal, 2);
        $tax = round((float) $customerReturn->tax_amount, 2);
        $amount = round((float) $transaction->amount, 2);
        if (round($subtotal + $tax, 2) !== $amount) {
            throw ValidationException::withMessages(['accounting' => 'Nilai refund tidak sama dengan subtotal dan pajak retur.']);
        }

        $returns = $this->account('4200', 'revenue');
        $lines = [
            ['chart_of_account_id' => $returns->id, 'line_number' => 1, 'description' => "Retur penjualan {$customerReturn->number}", 'debit' => $subtotal, 'credit' => 0],
        ];
        if ($tax > 0) {
            $taxAccount = $this->account('2120', 'liability');
            $lines[] = ['chart_of_account_id' => $taxAccount->id, 'line_number' => 2, 'description' => "Pembatalan pajak retur {$customerReturn->number}", 'debit' => $tax, 'credit' => 0];
        }
        $lines[] = ['chart_of_account_id' => $cashCoa->id, 'line_number' => count($lines) + 1, 'description' => "Refund customer {$customerReturn->number}", 'debit' => 0, 'credit' => $amount];

        return $this->createPosted(
            sourceType: CashBankTransaction::class,
            sourceId: $transaction->id,
            date: $transaction->transaction_date,
            reference: $customerReturn->number,
            description: "Refund Customer Return {$customerReturn->number}",
            lines: $lines,
            total: $amount,
            userId: $userId,
            sourceAction: 'original',
        );
    }

    public function recordManualCashBankTransaction(\App\Models\CashBankTransaction $transaction, int $userId): JournalEntry
    {
        if ($entry = $this->existing(\App\Models\CashBankTransaction::class, $transaction->id, 'current')) return $entry;
        $transaction->loadMissing(['account.chartOfAccount', 'counterAccount']);
        $cashCoa = $transaction->account?->chartOfAccount;
        $counter = $transaction->counterAccount;
        if (!$cashCoa || !$counter || !$cashCoa->is_active || !$counter->is_active || $cashCoa->is_group || $counter->is_group || $cashCoa->account_type !== 'asset' || $cashCoa->company_id !== $this->company->id() || $counter->company_id !== $this->company->id() || $cashCoa->id === $counter->id) {
            throw ValidationException::withMessages(['accounting' => 'Akun kas/bank dan akun lawan harus berupa dua akun COA transaksi yang aktif dan berbeda.']);
        }

        $amount = round((float) $transaction->amount, 2);
        $debitAccount = $transaction->direction === 'in' ? $cashCoa : $counter;
        $creditAccount = $transaction->direction === 'in' ? $counter : $cashCoa;
        return $this->createPosted(
            sourceType: \App\Models\CashBankTransaction::class,
            sourceId: $transaction->id,
            date: $transaction->transaction_date,
            reference: $transaction->reference_number,
            description: "Mutasi kas/bank: {$transaction->description}",
            lines: [
                ['chart_of_account_id' => $debitAccount->id, 'line_number' => 1, 'description' => $transaction->description, 'debit' => $amount, 'credit' => 0],
                ['chart_of_account_id' => $creditAccount->id, 'line_number' => 2, 'description' => $transaction->description, 'debit' => 0, 'credit' => $amount],
            ],
            total: $amount,
            userId: $userId,
            sourceAction: 'current',
        );
    }

    public function recordStockMovement(StockMovement $movement, int $userId, ?float $supplierValue = null): ?JournalEntry
    {
        $amount = round((float) $movement->cost_amount, 2);
        if ($amount <= 0) return null;
        if ($entry = $this->existing(StockMovement::class, $movement->id)) return $entry;

        $movement->loadMissing(['goodsReceipt', 'purchaseReturn', 'customerReturn', 'delivery', 'stockOpname', 'posSale']);
        $inventory = $this->account('1140', 'asset');
        $lines = [];
        $reference = $movement->goodsReceipt?->number ?? $movement->purchaseReturn?->number
            ?? $movement->customerReturn?->number
            ?? $movement->delivery?->number ?? $movement->stockOpname?->number ?? $movement->posSale?->number;
        $description = $movement->notes ?: "Mutasi persediaan barang #{$movement->id}";

        if ($movement->movement_type === 'purchase_receipt') {
            $clearing = $this->account('2130', 'liability');
            $lines = [
                ['chart_of_account_id' => $inventory->id, 'line_number' => 1, 'description' => $description, 'debit' => $amount, 'credit' => 0],
                ['chart_of_account_id' => $clearing->id, 'line_number' => 2, 'description' => $description, 'debit' => 0, 'credit' => $amount],
            ];
        } elseif ($movement->movement_type === 'purchase_return') {
            $clearing = $this->account('2130', 'liability');
            $variance = $this->account('5200', 'expense');
            $referenceValue = round($supplierValue ?? $amount, 2);
            $lines = [
                ['chart_of_account_id' => $clearing->id, 'line_number' => 1, 'description' => $description, 'debit' => $referenceValue, 'credit' => 0],
                ['chart_of_account_id' => $inventory->id, 'line_number' => 2, 'description' => $description, 'debit' => 0, 'credit' => $amount],
            ];
            if ($amount > $referenceValue) {
                $lines[] = ['chart_of_account_id' => $variance->id, 'line_number' => 3, 'description' => $description, 'debit' => $amount - $referenceValue, 'credit' => 0];
            } elseif ($referenceValue > $amount) {
                $lines[] = ['chart_of_account_id' => $variance->id, 'line_number' => 3, 'description' => $description, 'debit' => 0, 'credit' => $referenceValue - $amount];
            }
        } elseif (in_array($movement->movement_type, ['customer_return', 'pos_void'], true)) {
            $cogs = $this->account('5100', 'expense');
            $lines = [
                ['chart_of_account_id' => $inventory->id, 'line_number' => 1, 'description' => $description, 'debit' => $amount, 'credit' => 0],
                ['chart_of_account_id' => $cogs->id, 'line_number' => 2, 'description' => $description, 'debit' => 0, 'credit' => $amount],
            ];
        } elseif (in_array($movement->movement_type, ['sales_delivery', 'pos_sale'], true)) {
            $cogs = $this->account('5100', 'expense');
            $lines = [
                ['chart_of_account_id' => $cogs->id, 'line_number' => 1, 'description' => $description, 'debit' => $amount, 'credit' => 0],
                ['chart_of_account_id' => $inventory->id, 'line_number' => 2, 'description' => $description, 'debit' => 0, 'credit' => $amount],
            ];
        } elseif ($movement->movement_type === 'stock_adjustment') {
            if ((float) $movement->quantity > 0) {
                $gain = $this->account('4300', 'revenue');
                $lines = [
                    ['chart_of_account_id' => $inventory->id, 'line_number' => 1, 'description' => $description, 'debit' => $amount, 'credit' => 0],
                    ['chart_of_account_id' => $gain->id, 'line_number' => 2, 'description' => $description, 'debit' => 0, 'credit' => $amount],
                ];
            } else {
                $loss = $this->account('5300', 'expense');
                $lines = [
                    ['chart_of_account_id' => $loss->id, 'line_number' => 1, 'description' => $description, 'debit' => $amount, 'credit' => 0],
                    ['chart_of_account_id' => $inventory->id, 'line_number' => 2, 'description' => $description, 'debit' => 0, 'credit' => $amount],
                ];
            }
        } else {
            return null;
        }

        $total = array_sum(array_column($lines, 'debit'));
        return $this->createPosted(
            sourceType: StockMovement::class,
            sourceId: $movement->id,
            date: $movement->created_at,
            reference: $reference,
            description: $description,
            lines: $lines,
            total: $total,
            userId: $userId,
            sourceAction: 'original',
        );
    }

    public function recordPosSale(PosSale $sale, int $userId): JournalEntry
    {
        if ($entry = $this->existing(PosSale::class, $sale->id)) return $entry;
        $sale->loadMissing(['cashBankAccount.chartOfAccount', 'items']);
        $cashCoa = $sale->cashBankAccount?->chartOfAccount;
        if (!$cashCoa || !$cashCoa->is_active || $cashCoa->is_group || $cashCoa->account_type !== 'asset' || $cashCoa->company_id !== $this->company->id()) {
            throw ValidationException::withMessages(['accounting' => 'Akun pembayaran POS belum dipetakan ke COA aset aktif.']);
        }
        $netSales = round((float) $sale->subtotal - (float) $sale->discount_amount, 2);
        $tax = round((float) $sale->tax_amount, 2);
        $total = round((float) $sale->total_amount, 2);
        if (round($netSales + $tax, 2) !== $total) {
            throw ValidationException::withMessages(['accounting' => 'Total penjualan POS tidak seimbang; jurnal tidak dibuat.']);
        }
        $revenue = $this->account('4100', 'revenue');
        $lines = [
            ['chart_of_account_id' => $cashCoa->id, 'line_number' => 1, 'description' => "Pembayaran POS {$sale->number}", 'debit' => $total, 'credit' => 0],
            ['chart_of_account_id' => $revenue->id, 'line_number' => 2, 'description' => "Penjualan POS {$sale->number}", 'debit' => 0, 'credit' => $netSales],
        ];
        if ($tax > 0) {
            $taxAccount = $this->account('2120', 'liability');
            $lines[] = ['chart_of_account_id' => $taxAccount->id, 'line_number' => 3, 'description' => "Pajak POS {$sale->number}", 'debit' => 0, 'credit' => $tax];
        }
        return $this->createPosted(
            sourceType: PosSale::class,
            sourceId: $sale->id,
            date: $sale->sold_at,
            reference: $sale->number,
            description: "Penjualan POS {$sale->number}",
            lines: $lines,
            total: $total,
            userId: $userId,
            sourceAction: 'original',
        );
    }

    public function reversePosSale(PosSale $sale, int $userId): JournalEntry
    {
        $entry = $this->existing(PosSale::class, $sale->id);
        if (!$entry) {
            throw ValidationException::withMessages(['accounting' => 'Jurnal penjualan POS asli tidak ditemukan; void dibatalkan.']);
        }

        return $this->reverse($entry, $userId);
    }

    public function recordNonStockReceipt(GoodsReceiptItem $item, GoodsReceipt $receipt, int $userId): ?JournalEntry
    {
        $amount = round((float) $item->line_total, 2);
        if ($amount <= 0) return null;
        if ($entry = $this->existing(GoodsReceiptItem::class, $item->id)) return $entry;
        $expense = $this->account('6100', 'expense');
        $clearing = $this->account('2130', 'liability');
        $description = "Pembelian non-persediaan dari penerimaan {$receipt->number}";
        return $this->createPosted(
            sourceType: GoodsReceiptItem::class,
            sourceId: $item->id,
            date: $receipt->received_at,
            reference: $receipt->number,
            description: $description,
            lines: [
                ['chart_of_account_id' => $expense->id, 'line_number' => 1, 'description' => $description, 'debit' => $amount, 'credit' => 0],
                ['chart_of_account_id' => $clearing->id, 'line_number' => 2, 'description' => $description, 'debit' => 0, 'credit' => $amount],
            ],
            total: $amount,
            userId: $userId,
            sourceAction: 'original',
        );
    }

    public function recordNonStockPurchaseReturn(PurchaseReturnItem $item, string $receiptNumber, string $returnNumber, $date, int $userId): ?JournalEntry
    {
        $amount = round((float) $item->line_total, 2);
        if ($amount <= 0) return null;
        if ($entry = $this->existing(PurchaseReturnItem::class, $item->id)) return $entry;
        $expense = $this->account('6100', 'expense');
        $clearing = $this->account('2130', 'liability');
        $description = "Retur barang non-persediaan {$returnNumber} dari {$receiptNumber}";
        return $this->createPosted(
            sourceType: PurchaseReturnItem::class,
            sourceId: $item->id,
            date: $date,
            reference: $returnNumber,
            description: $description,
            lines: [
                ['chart_of_account_id' => $clearing->id, 'line_number' => 1, 'description' => $description, 'debit' => $amount, 'credit' => 0],
                ['chart_of_account_id' => $expense->id, 'line_number' => 2, 'description' => $description, 'debit' => 0, 'credit' => $amount],
            ],
            total: $amount,
            userId: $userId,
            sourceAction: 'original',
        );
    }

    public function reviseManualCashBankTransaction(\App\Models\CashBankTransaction $transaction, int $userId): void
    {
        $current = $this->existing(\App\Models\CashBankTransaction::class, $transaction->id, 'current');
        if ($current) {
            $this->reverse($current, $userId);
            $current->update(['source_action' => 'superseded-' . $current->id]);
        }
        $this->recordManualCashBankTransaction($transaction->fresh(), $userId);
    }

    public function reverseManualCashBankTransaction(\App\Models\CashBankTransaction $transaction, int $userId): void
    {
        $current = $this->existing(\App\Models\CashBankTransaction::class, $transaction->id, 'current');
        if (!$current) return;
        $this->reverse($current, $userId);
        $current->update(['source_action' => 'superseded-' . $current->id]);
    }

    private function account(string $code, string $type): ChartOfAccount
    {
        $account = ChartOfAccount::query()->where('company_id', $this->company->id())->where('code', $code)
            ->where('account_type', $type)->where('is_active', true)->where('is_group', false)->first();
        if (!$account) throw ValidationException::withMessages(['accounting' => "Akun COA {$code} belum tersedia atau tidak aktif. Jalankan ChartOfAccountsSeeder dan periksa status akun."]);
        return $account;
    }

    private function existing(string $sourceType, int $sourceId, string $sourceAction = 'original'): ?JournalEntry
    {
        return JournalEntry::query()->where('company_id', $this->company->id())
            ->where('source_type', $sourceType)->where('source_id', $sourceId)->where('source_action', $sourceAction)->first();
    }

    private function reverse(JournalEntry $entry, int $userId): JournalEntry
    {
        $entry->loadMissing('lines');
        $lines = $entry->lines->map(fn($line, $index) => [
            'chart_of_account_id' => $line->chart_of_account_id,
            'line_number' => $index + 1,
            'description' => "Pembalik: " . ($line->description ?: $entry->description),
            'debit' => (float) $line->credit,
            'credit' => (float) $line->debit,
        ])->all();
        return $this->createPosted(
            sourceType: $entry->source_type,
            sourceId: $entry->source_id,
            date: today(),
            reference: $entry->number,
            description: "Pembalik jurnal {$entry->number}",
            lines: $lines,
            total: (float) $entry->total_debit,
            userId: $userId,
            sourceAction: 'reversal-' . $entry->id,
        );
    }

    private function createPosted(string $sourceType, int $sourceId, $date, ?string $reference, string $description, array $lines, float $total, int $userId, string $sourceAction): JournalEntry
    {
        $entry = JournalEntry::create([
            'company_id' => $this->company->id(),
            'number' => $this->numbers->generate('journal_entry'),
            'journal_date' => $date,
            'reference_number' => $reference,
            'description' => $description,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_action' => $sourceAction,
            'total_debit' => $total,
            'total_credit' => $total,
            'status' => 'posted',
            'created_by' => $userId,
            'posted_by' => $userId,
            'posted_at' => now(),
        ]);
        $entry->lines()->createMany($lines);
        $this->auditLog->log(action: 'AUTO_POST', module: 'JOURNAL', description: "Membuat jurnal otomatis {$entry->number}: {$description}", model: $entry, oldValues: null, newValues: $entry->load('lines')->toArray());
        return $entry->load('lines.account');
    }
}

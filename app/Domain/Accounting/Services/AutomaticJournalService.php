<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\CashBankAccount;
use App\Models\CashBankTransaction;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\StockMovement;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseReturnItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AutomaticJournalService
{
    public function __construct(private CompanyContext $company, private DocumentNumberService $numbers, private AuditLogService $auditLog) {}

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

        $movement->loadMissing(['goodsReceipt', 'purchaseReturn', 'stockOpname', 'damagedItemDocument', 'lostItemDocument', 'nonProductionMaterialUsageDocument']);
        $inventory = $this->account('1140', 'asset');
        $lines = [];
        $reference = $movement->goodsReceipt?->number ?? $movement->purchaseReturn?->number
            ?? $movement->stockOpname?->number ?? $movement->damagedItemDocument?->number ?? $movement->lostItemDocument?->number ?? $movement->nonProductionMaterialUsageDocument?->number;
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
        } elseif ($movement->movement_type === 'damaged_item') {
            $loss = $this->account('5300', 'expense');
            $lines = [
                ['chart_of_account_id' => $loss->id, 'line_number' => 1, 'description' => $description, 'debit' => $amount, 'credit' => 0],
                ['chart_of_account_id' => $inventory->id, 'line_number' => 2, 'description' => $description, 'debit' => 0, 'credit' => $amount],
            ];
        } elseif ($movement->movement_type === 'damaged_item_reversal') {
            $loss = $this->account('5300', 'expense');
            $lines = [
                ['chart_of_account_id' => $inventory->id, 'line_number' => 1, 'description' => $description, 'debit' => $amount, 'credit' => 0],
                ['chart_of_account_id' => $loss->id, 'line_number' => 2, 'description' => $description, 'debit' => 0, 'credit' => $amount],
            ];
        } elseif ($movement->movement_type === 'lost_item') {
            $loss = $this->account('5300', 'expense');
            $lines = [
                ['chart_of_account_id' => $loss->id, 'line_number' => 1, 'description' => $description, 'debit' => $amount, 'credit' => 0],
                ['chart_of_account_id' => $inventory->id, 'line_number' => 2, 'description' => $description, 'debit' => 0, 'credit' => $amount],
            ];
        } elseif ($movement->movement_type === 'lost_item_reversal') {
            $loss = $this->account('5300', 'expense');
            $lines = [
                ['chart_of_account_id' => $inventory->id, 'line_number' => 1, 'description' => $description, 'debit' => $amount, 'credit' => 0],
                ['chart_of_account_id' => $loss->id, 'line_number' => 2, 'description' => $description, 'debit' => 0, 'credit' => $amount],
            ];
        } elseif ($movement->movement_type === 'non_production_material_usage') {
            $expense = $this->account('6100', 'expense');
            $lines = [
                ['chart_of_account_id' => $expense->id, 'line_number' => 1, 'description' => $description, 'debit' => $amount, 'credit' => 0],
                ['chart_of_account_id' => $inventory->id, 'line_number' => 2, 'description' => $description, 'debit' => 0, 'credit' => $amount],
            ];
        } elseif ($movement->movement_type === 'non_production_material_usage_reversal') {
            $expense = $this->account('6100', 'expense');
            $lines = [
                ['chart_of_account_id' => $inventory->id, 'line_number' => 1, 'description' => $description, 'debit' => $amount, 'credit' => 0],
                ['chart_of_account_id' => $expense->id, 'line_number' => 2, 'description' => $description, 'debit' => 0, 'credit' => $amount],
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

    public function reverseStockMovementJournals(StockMovement $movement, int $userId): void
    {
        $this->reverseSourceEntries(StockMovement::class, (int) $movement->id, $userId);
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

    public function reverseGoodsReceiptJournals(GoodsReceipt $receipt, int $userId): void
    {
        $sourceIds = StockMovement::query()->where('goods_receipt_id', $receipt->id)->pluck('id');
        foreach ($sourceIds as $sourceId) {
            $this->reverseSourceEntries(StockMovement::class, (int) $sourceId, $userId);
        }

        foreach ($receipt->items as $item) {
            $this->reverseSourceEntries(GoodsReceiptItem::class, (int) $item->id, $userId);
        }
    }

    public function reversePurchaseReturnJournals(\App\Models\PurchaseReturn $purchaseReturn, int $userId): void
    {
        $movementIds = StockMovement::query()->where('purchase_return_id', $purchaseReturn->id)->pluck('id');
        foreach ($movementIds as $movementId) {
            $this->reverseSourceEntries(StockMovement::class, (int) $movementId, $userId);
        }

        foreach ($purchaseReturn->items as $item) {
            $this->reverseSourceEntries(PurchaseReturnItem::class, (int) $item->id, $userId);
        }
    }

    private function reverseSourceEntries(string $sourceType, int $sourceId, int $userId): void
    {
        JournalEntry::query()
            ->where('company_id', $this->company->id())
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where(fn ($query) => $query->where('source_action', 'original')->orWhereNull('source_action'))
            ->where('status', 'posted')
            ->with('lines')
            ->get()
            ->each(fn (JournalEntry $entry) => $this->reverse($entry, $userId));
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

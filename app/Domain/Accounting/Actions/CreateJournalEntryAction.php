<?php

namespace App\Domain\Accounting\Actions;

use App\Domain\Accounting\DTOs\JournalEntryData;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateJournalEntryAction
{
    public function __construct(private CompanyContext $company, private DocumentNumberService $numbers, private AuditLogService $auditLog) {}

    public function __invoke(JournalEntryData $data, int $userId): JournalEntry
    {
        return DB::transaction(function () use ($data, $userId) {
            $attributes = $data->attributes;
            $lines = $attributes['lines'];
            [$debitCents, $creditCents] = $this->validateAndTotal($lines);
            if ($debitCents !== $creditCents || $debitCents <= 0) {
                throw ValidationException::withMessages(['lines' => 'Total debit dan kredit harus sama dan lebih dari nol.']);
            }

            $entry = JournalEntry::create([
                'company_id' => $this->company->id(),
                'number' => $this->numbers->generate('journal_entry'),
                'journal_date' => $attributes['journal_date'],
                'reference_number' => $attributes['reference_number'] ?? null,
                'description' => $attributes['description'],
                'total_debit' => $debitCents / 100,
                'total_credit' => $creditCents / 100,
                'status' => 'draft',
                'created_by' => $userId,
            ]);
            $entry->lines()->createMany($this->lineAttributes($lines));
            $entry->load('lines.account');
            $this->auditLog->log(action: 'CREATE', module: 'JOURNAL', description: "Membuat draft jurnal {$entry->number}", model: $entry, oldValues: null, newValues: $entry->toArray());
            return $entry;
        });
    }

    private function validateAndTotal(array $lines): array
    {
        $debit = $credit = 0;
        foreach ($lines as $index => $line) {
            $account = ChartOfAccount::query()->where('company_id', $this->company->id())->where('is_active', true)->where('is_group', false)->find($line['chart_of_account_id']);
            if (!$account) throw ValidationException::withMessages(["lines.{$index}.chart_of_account_id" => 'Pilih akun transaksi aktif milik perusahaan ini.']);
            $lineDebit = (int) round((float) $line['debit'] * 100);
            $lineCredit = (int) round((float) $line['credit'] * 100);
            if (($lineDebit > 0) === ($lineCredit > 0)) {
                throw ValidationException::withMessages(["lines.{$index}.debit" => 'Setiap baris harus mengisi debit atau kredit saja.']);
            }
            $debit += $lineDebit;
            $credit += $lineCredit;
        }
        return [$debit, $credit];
    }

    private function lineAttributes(array $lines): array
    {
        return collect($lines)->values()->map(fn ($line, $index) => [
            'chart_of_account_id' => $line['chart_of_account_id'],
            'line_number' => $index + 1,
            'description' => $line['description'] ?? null,
            'debit' => $line['debit'],
            'credit' => $line['credit'],
        ])->all();
    }
}

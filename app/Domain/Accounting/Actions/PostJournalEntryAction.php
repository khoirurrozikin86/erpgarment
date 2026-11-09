<?php

namespace App\Domain\Accounting\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostJournalEntryAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $auditLog) {}

    public function __invoke(JournalEntry $entry, int $userId): JournalEntry
    {
        return DB::transaction(function () use ($entry, $userId) {
            $entry = JournalEntry::query()->where('company_id', $this->company->id())->lockForUpdate()->with('lines')->findOrFail($entry->id);
            if ($entry->status !== 'draft') throw ValidationException::withMessages(['entry' => 'Hanya jurnal draft yang dapat diposting.']);

            $debit = $credit = 0;
            foreach ($entry->lines as $line) {
                $account = ChartOfAccount::query()->where('company_id', $this->company->id())->where('is_active', true)->where('is_group', false)->find($line->chart_of_account_id);
                if (!$account) throw ValidationException::withMessages(['entry' => 'Jurnal memakai akun yang sudah nonaktif atau tidak valid.']);
                $debit += (int) round((float) $line->debit * 100);
                $credit += (int) round((float) $line->credit * 100);
            }
            if ($debit <= 0 || $debit !== $credit) throw ValidationException::withMessages(['entry' => 'Jurnal tidak seimbang dan tidak dapat diposting.']);

            $oldValues = $entry->only(['status', 'posted_by', 'posted_at']);
            $entry->update(['status' => 'posted', 'posted_by' => $userId, 'posted_at' => now()]);
            $this->auditLog->log(action: 'POST', module: 'JOURNAL', description: "Memposting jurnal {$entry->number}", model: $entry, oldValues: $oldValues, newValues: $entry->fresh()->only(['status', 'posted_by', 'posted_at']));
            return $entry->fresh(['lines.account', 'poster']);
        });
    }
}

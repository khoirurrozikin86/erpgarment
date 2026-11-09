<?php

namespace App\Domain\Accounting\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteDraftJournalEntryAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $auditLog) {}

    public function __invoke(JournalEntry $entry): void
    {
        DB::transaction(function () use ($entry) {
            $entry = JournalEntry::query()->where('company_id', $this->company->id())->lockForUpdate()->with('lines')->findOrFail($entry->id);
            if ($entry->status !== 'draft') throw ValidationException::withMessages(['entry' => 'Jurnal yang sudah diposting tidak dapat dihapus.']);
            $this->auditLog->log(action: 'DELETE', module: 'JOURNAL', description: "Menghapus draft jurnal {$entry->number}", model: $entry, oldValues: $entry->toArray(), newValues: null);
            $entry->delete();
        });
    }
}

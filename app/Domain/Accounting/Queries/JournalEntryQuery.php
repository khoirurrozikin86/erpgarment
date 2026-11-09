<?php

namespace App\Domain\Accounting\Queries;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Builder;

class JournalEntryQuery
{
    use AppliesDateRange;

    public function __construct(private CompanyContext $company) {}

    public function builder(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = JournalEntry::query()->where('company_id', $this->company->id())
            ->with(['creator:id,name', 'poster:id,name'])->withCount('lines')
            ->orderByDesc('journal_date')->orderByDesc('id');
        return $this->applyDateRange($query, 'journal_date', $fromDate, $toDate);
    }

    public function accounts()
    {
        return ChartOfAccount::query()->where('company_id', $this->company->id())
            ->where('is_active', true)->where('is_group', false)->orderBy('code')
            ->get(['id', 'code', 'name', 'account_type', 'normal_balance']);
    }

    public function find(int $id): JournalEntry
    {
        return JournalEntry::query()->where('company_id', $this->company->id())
            ->with(['creator:id,name', 'poster:id,name', 'lines.account:id,code,name,account_type'])
            ->findOrFail($id);
    }
}

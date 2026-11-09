<?php

namespace App\Domain\Accounting\Queries;

use App\Domain\Companies\Services\CompanyContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProfitLossQuery
{
    public function __construct(private CompanyContext $company) {}

    public function rows(string $fromDate, string $toDate): Collection
    {
        return DB::table('journal_entry_lines as lines')
            ->join('journal_entries as entries', 'entries.id', '=', 'lines.journal_entry_id')
            ->join('chart_of_accounts as accounts', 'accounts.id', '=', 'lines.chart_of_account_id')
            ->where('entries.company_id', $this->company->id())
            ->where('entries.status', 'posted')
            ->whereBetween('entries.journal_date', [$fromDate, $toDate])
            ->whereIn('accounts.account_type', ['revenue', 'expense'])
            ->where('accounts.is_group', false)
            ->select('accounts.id', 'accounts.code', 'accounts.name', 'accounts.account_type')
            ->selectRaw('SUM(lines.debit) as debit_total, SUM(lines.credit) as credit_total')
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.account_type')
            ->orderBy('accounts.account_type')->orderBy('accounts.code')->get()
            ->map(function ($row) {
                $row->amount = round($row->account_type === 'revenue'
                    ? (float) $row->credit_total - (float) $row->debit_total
                    : (float) $row->debit_total - (float) $row->credit_total, 2);
                return $row;
            })->filter(fn ($row) => $row->amount != 0)->values();
    }
}

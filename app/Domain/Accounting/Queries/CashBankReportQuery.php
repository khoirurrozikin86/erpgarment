<?php

namespace App\Domain\Accounting\Queries;

use App\Domain\Companies\Services\CompanyContext;
use App\Models\CashBankAccount;
use Illuminate\Database\Eloquent\Builder;

class CashBankReportQuery
{
    public function __construct(private CompanyContext $company) {}

    public function accounts(string $fromDate, string $toDate): Builder
    {
        return CashBankAccount::query()->where('company_id', $this->company->id())
            ->with('chartOfAccount:id,code,name')
            ->withSum(['transactions as prior_in' => fn (Builder $query) => $query
                ->where('transaction_date', '<', $fromDate)->where('direction', 'in')], 'amount')
            ->withSum(['transactions as prior_out' => fn (Builder $query) => $query
                ->where('transaction_date', '<', $fromDate)->where('direction', 'out')], 'amount')
            ->withSum(['transactions as period_in' => fn (Builder $query) => $query
                ->whereBetween('transaction_date', [$fromDate, $toDate])->where('direction', 'in')], 'amount')
            ->withSum(['transactions as period_out' => fn (Builder $query) => $query
                ->whereBetween('transaction_date', [$fromDate, $toDate])->where('direction', 'out')], 'amount')
            ->orderBy('type')->orderBy('code');
    }
}

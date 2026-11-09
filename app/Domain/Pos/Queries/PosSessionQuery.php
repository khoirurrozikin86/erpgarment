<?php

namespace App\Domain\Pos\Queries;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use App\Models\PosSession;
use App\Models\CashBankAccount;
use Illuminate\Database\Eloquent\Builder;

class PosSessionQuery
{
    use AppliesDateRange;

    public function __construct(private CompanyContext $company) {}

    public function sessions(?string $fromDate, ?string $toDate): Builder
    {
        $query = PosSession::query()->where('company_id', $this->company->id())
            ->with(['cashBankAccount:id,code,name', 'opener:id,name', 'closer:id,name'])
            ->withSum(['cashBankTransactions as cash_in_total' => fn (Builder $transactions) => $transactions->whereColumn('cash_bank_account_id', 'pos_sessions.cash_bank_account_id')->where('direction', 'in')], 'amount')
            ->withSum(['cashBankTransactions as cash_out_total' => fn (Builder $transactions) => $transactions->whereColumn('cash_bank_account_id', 'pos_sessions.cash_bank_account_id')->where('direction', 'out')], 'amount')
            ->orderByDesc('opened_at')->orderByDesc('id');
        return $this->applyDateRange($query, 'opened_at', $fromDate, $toDate);
    }

    public function activeCashAccounts()
    {
        return \App\Models\CashBankAccount::query()->where('company_id', $this->company->id())
            ->where('type', 'cash')->where('is_active', true)
            ->whereHas('chartOfAccount', fn (Builder $query) => $query->where('is_active', true)->where('is_group', false)->where('account_type', 'asset'))
            ->orderBy('code')->get(['id', 'code', 'name']);
    }

    public function activeSession(int $userId): ?PosSession
    {
        return PosSession::query()->where('company_id', $this->company->id())->where('opened_by', $userId)
            ->where('status', 'open')->with('cashBankAccount:id,code,name')->latest('opened_at')->first();
    }

    public function activePaymentAccounts()
    {
        return CashBankAccount::query()->where('company_id', $this->company->id())->where('is_active', true)
            ->whereHas('chartOfAccount', fn (Builder $query) => $query->where('is_active', true)->where('is_group', false)->where('account_type', 'asset'))
            ->orderBy('type')->orderBy('code')->get(['id', 'code', 'name', 'type']);
    }
}

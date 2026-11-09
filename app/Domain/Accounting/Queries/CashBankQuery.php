<?php

namespace App\Domain\Accounting\Queries;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use App\Models\CashBankAccount;
use App\Models\CashBankTransaction;
use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Builder;

class CashBankQuery
{
    use AppliesDateRange;

    public function __construct(private CompanyContext $company) {}

    public function accounts(): Builder
    {
        return CashBankAccount::query()->where('company_id', $this->company->id())->with('chartOfAccount:id,code,name')
            ->withSum(['transactions as incoming_total' => fn(Builder $query) => $query->where('direction', 'in')], 'amount')
            ->withSum(['transactions as outgoing_total' => fn(Builder $query) => $query->where('direction', 'out')], 'amount')
            ->orderBy('type')->orderBy('code');
    }

    public function transactions(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = CashBankTransaction::query()->where('company_id', $this->company->id())
            ->with(['account:id,code,name,type', 'counterAccount:id,code,name', 'payment.invoice:id,number', 'supplierPayment.goodsReceipt:id,number', 'customerReturn:id,number', 'posSession:id,number', 'posSale:id,number', 'posSaleVoid:id,number', 'creator:id,name'])
            ->orderByDesc('transaction_date')->orderByDesc('id');
        return $this->applyDateRange($query, 'transaction_date', $fromDate, $toDate);
    }

    public function activeAccounts()
    {
        return CashBankAccount::query()->where('company_id', $this->company->id())->where('is_active', true)
            ->whereHas('chartOfAccount', fn(Builder $query) => $query->where('is_active', true)->where('is_group', false)->where('account_type', 'asset'))
            ->orderBy('type')->orderBy('code')->get(['id', 'code', 'name', 'type']);
    }

    public function activeCashBankChartAccounts()
    {
        return ChartOfAccount::query()->where('company_id', $this->company->id())->where('account_type', 'asset')
            ->where('is_group', false)->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);
    }

    public function activeTransactionChartAccounts()
    {
        return ChartOfAccount::query()->where('company_id', $this->company->id())->where('is_group', false)->where('is_active', true)
            ->orderBy('code')->get(['id', 'code', 'name', 'account_type']);
    }
}

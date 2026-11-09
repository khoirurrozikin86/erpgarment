<?php

namespace App\Domain\Accounting\Queries;

use App\Domain\Companies\Services\CompanyContext;
use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Builder;

class ChartOfAccountQuery
{
    public function __construct(private CompanyContext $company) {}

    public function builder(): Builder
    {
        return ChartOfAccount::query()->where('company_id', $this->company->id())
            ->with(['parent:id,code,name'])
            ->withCount('children')
            ->orderBy('code');
    }

    public function parentOptions(?int $exceptId = null)
    {
        return ChartOfAccount::query()->where('company_id', $this->company->id())
            ->where('is_group', true)->where('is_active', true)
            ->when($exceptId, fn (Builder $query) => $query->where('id', '!=', $exceptId))
            ->orderBy('code')->get(['id', 'code', 'name', 'account_type']);
    }
}

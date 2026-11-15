<?php

namespace App\Domain\Subcontract\Queries;

use App\Models\{SubcontractContract, Subcontractor};
use Illuminate\Database\Eloquent\Builder;

class SubcontractTableQuery
{
    public function subcontractors(int $companyId): Builder
    {
        return Subcontractor::query()->where('company_id', $companyId)->select('subcontractors.*')->orderBy('name');
    }

    public function contracts(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return SubcontractContract::query()->where('company_id', $companyId)
            ->when($from, fn ($q) => $q->whereDate('starts_on', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('starts_on', '<=', $to))
            ->with('subcontractor:id,name')->select('subcontract_contracts.*')->latest('starts_on');
    }
}

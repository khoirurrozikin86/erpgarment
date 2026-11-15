<?php

namespace App\Domain\Inventory\Queries;

use App\Models\{EquipmentIssueDocument, Product};
use Illuminate\Database\Eloquent\Builder;

class EquipmentIssueTableQuery
{
    public function documents(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return EquipmentIssueDocument::query()->where('company_id', $companyId)
            ->when($from, fn (Builder $q) => $q->whereDate('issue_date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('issue_date', '<=', $to))
            ->with('item')->select('equipment_issue_documents.*');
    }

    public function products(): Builder
    {
        return Product::query()->where('is_active', true)->whereHas('category', fn (Builder $q) => $q->whereIn('code', ['MACHINE', 'EQUIPMENT']))
            ->with(['unit:id,name,symbol', 'category:id,name'])->select(['id', 'code', 'name', 'unit_id', 'category_id'])->orderBy('code');
    }
}

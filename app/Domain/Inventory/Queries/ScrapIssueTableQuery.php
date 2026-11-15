<?php

namespace App\Domain\Inventory\Queries;

use App\Models\{Customer, Product, ScrapIssueDocument};
use Illuminate\Database\Eloquent\Builder;

class ScrapIssueTableQuery
{
    public function builder(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return ScrapIssueDocument::query()->where('company_id', $companyId)
            ->when($from, fn (Builder $q) => $q->whereDate('issue_date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('issue_date', '<=', $to))
            ->with(['customer:id,name', 'scrapProduct:id,code,name,unit_id', 'scrapProduct.unit:id,name,symbol'])
            ->select('scrap_issue_documents.*')->latest('issue_date')->latest('id');
    }

    public function scrapProducts(): Builder
    {
        return Product::query()->where('track_stock', true)->where('is_active', true)
            ->whereHas('category', fn (Builder $q) => $q->where('code', 'WASTE'))
            ->with('unit:id,name,symbol')->select(['id', 'code', 'name', 'unit_id'])->orderBy('code');
    }

    public function customers(): Builder
    {
        return Customer::query()->where('is_active', true)->select(['id', 'code', 'name'])->orderBy('name');
    }
}

<?php

namespace App\Domain\Inventory\Queries;

use App\Models\Product;
use App\Models\WipIssueLine;
use Illuminate\Database\Eloquent\Builder;

class WipIssueTableQuery
{
    public function builder(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return WipIssueLine::query()
            ->whereHas('document', fn (Builder $query) => $query->where('company_id', $companyId)
                ->when($from, fn (Builder $query) => $query->whereDate('transaction_date', '>=', $from))
                ->when($to, fn (Builder $query) => $query->whereDate('transaction_date', '<=', $to)))
            ->with(['document:id,company_id,number,transaction_date', 'product:id,code,name,unit_id,style_code', 'product.unit:id,name,symbol'])
            ->select('wip_issue_lines.*')->latest('wip_issue_lines.created_at');
    }

    public function products(): Builder
    {
        return Product::query()->where('track_stock', true)->where('is_active', true)
            ->where(function (Builder $query) {
                $query->whereHas('category', fn (Builder $category) => $category->where('code', 'WIP')->orWhereRaw('LOWER(name) = ?', ['barang setengah jadi']))
                    ->orWhere('garment_item_type', 'work_in_process');
            })
            ->with('unit:id,name,symbol')->select(['id', 'code', 'name', 'unit_id', 'category_id'])->orderBy('name');
    }
}

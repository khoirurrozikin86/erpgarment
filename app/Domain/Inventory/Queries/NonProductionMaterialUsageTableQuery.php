<?php

namespace App\Domain\Inventory\Queries;

use App\Models\NonProductionMaterialUsageItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class NonProductionMaterialUsageTableQuery
{
    public function builder(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return NonProductionMaterialUsageItem::query()
            ->whereHas('document', fn (Builder $query) => $query->where('company_id', $companyId)
                ->when($from, fn (Builder $query) => $query->whereDate('transaction_date', '>=', $from))
                ->when($to, fn (Builder $query) => $query->whereDate('transaction_date', '<=', $to)))
            ->with(['document:id,company_id,number,transaction_date', 'product:id,code,name,unit_id,style_code', 'product.unit:id,name,symbol'])
            ->select('non_production_material_usage_items.*')
            ->latest('non_production_material_usage_items.created_at');
    }

    public function products(): Builder
    {
        return Product::query()->where('track_stock', true)->where('is_active', true)
            ->with('unit:id,name,symbol')->select(['id', 'code', 'name', 'unit_id'])->orderBy('name');
    }
}

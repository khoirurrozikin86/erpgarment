<?php

namespace App\Domain\Inventory\Queries;

use Illuminate\Database\Eloquent\Builder;

class WarehouseReturnReportQuery
{
    public function builder(int $companyId, array $filters): Builder
    {
        return app(ProductionReturnTableQuery::class)->items($companyId, $filters['from_date'], $filters['to_date'])
            ->with(['product:id,code,name,unit_id,style_code,parent_product_id,color,size', 'product.parentProduct:id,code'])
            ->whereHas('document', fn (Builder $q) => $q->where('status', 'approved'))
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $q) use ($search) {
                    $q->whereHas('document', fn (Builder $q) => $q->where('number', 'like', "%{$search}%"))
                        ->orWhereHas('product', fn (Builder $q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhereHas('parentProduct', fn (Builder $parent) => $parent->where('code', 'like', "%{$search}%")));
                });
            })->reorder('production_return_document_id')->orderBy('production_return_items.id');
    }
}

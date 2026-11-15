<?php

namespace App\Domain\Inventory\Queries;

use Illuminate\Database\Eloquent\Builder;

class FinishedGoodsMutationReportQuery extends MaterialMutationReportQuery
{
    protected function products(array $filters): Builder
    {
        return app(InventoryReportQuery::class)->builder($filters['from_date'], $filters['to_date'])
            ->where('product_type', 'stock')
            ->where(function (Builder $q) {
                $q->where('garment_item_type', 'finished_good')
                    ->orWhereHas('category', fn (Builder $q) => $q->where('code', 'FG')->orWhereRaw('LOWER(name) = ?', ['finished goods']));
            });
    }
}

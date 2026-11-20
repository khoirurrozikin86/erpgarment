<?php

namespace App\Domain\Inventory\Queries;

use Illuminate\Database\Eloquent\Builder;

class FinishedGoodsMutationReportQuery extends MaterialMutationReportQuery
{
    protected function products(array $filters): Builder
    {
        return $this->stockIdentityProducts($filters)
            ->without('stockMovements')
            ->addSelect(['products.color', 'products.size'])
            ->where(function (Builder $q) {
                $q->where('garment_item_type', 'finished_good')
                    ->orWhereHas('category', fn (Builder $q) => $q->where('code', 'FG')->orWhereRaw('LOWER(name) = ?', ['finished goods']));
            });
    }

    protected function search(Builder $products, string $pattern): Builder
    {
        return parent::search($products, $pattern)
            ->orWhere('products.color', 'like', $pattern)
            ->orWhere('products.size', 'like', $pattern);
    }
}

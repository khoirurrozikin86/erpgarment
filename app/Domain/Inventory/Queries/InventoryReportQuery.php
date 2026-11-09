<?php

namespace App\Domain\Inventory\Queries;

use App\Models\Product;
use App\Models\StockMovement;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class InventoryReportQuery
{
    public function builder(string $fromDate, string $toDate): Builder
    {
        $from = CarbonImmutable::parse($fromDate)->startOfDay();
        $until = CarbonImmutable::parse($toDate)->addDay()->startOfDay();

        return Product::query()->where('track_stock', true)
            ->with(['category:id,name', 'unit:id,name,symbol'])
            ->select(['id', 'code', 'name', 'category_id', 'unit_id', 'min_stock', 'reorder_point'])
            ->addSelect([
                'opening_balance' => StockMovement::query()->select('quantity_after')
                    ->whereColumn('product_id', 'products.id')->where('created_at', '<', $from)
                    ->orderByDesc('created_at')->orderByDesc('id')->limit(1),
            ])
            ->withSum(['stockMovements as quantity_in' => fn (Builder $query) => $query
                ->where('created_at', '>=', $from)->where('created_at', '<', $until)->where('quantity', '>', 0)], 'quantity')
            ->withSum(['stockMovements as quantity_out' => fn (Builder $query) => $query
                ->where('created_at', '>=', $from)->where('created_at', '<', $until)->where('quantity', '<', 0)], 'quantity')
            ->orderBy('name');
    }
}

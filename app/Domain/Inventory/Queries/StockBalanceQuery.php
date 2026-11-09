<?php

namespace App\Domain\Inventory\Queries;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class StockBalanceQuery
{
    public function builder(): Builder
    {
        return Product::query()
            ->where('track_stock', true)
            ->with(['category:id,name', 'unit:id,name,symbol', 'stock:id,product_id,quantity'])
            ->select([
                'id', 'code', 'name', 'category_id', 'unit_id',
                'min_stock', 'max_stock', 'reorder_point', 'track_stock',
            ])
            ->orderBy('name');
    }
}

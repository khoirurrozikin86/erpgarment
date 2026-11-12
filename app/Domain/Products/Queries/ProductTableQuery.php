<?php

namespace App\Domain\Products\Queries;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class ProductTableQuery
{
    public function builder(): Builder
    {
        return Product::query()
            ->with([
                'category:id,name',
                'unit:id,name,symbol',
            ])
            ->select([
                'id',
                'code',
                'name',
                'category_id',
                'unit_id',
                'product_type',
                'garment_item_type',
                'style_code',
                'color',
                'size',
                'purchase_price',
                'min_stock',
                'max_stock',
                'reorder_point',
                'track_stock',
                'is_active',
                'is_available',
                'created_at',
                'updated_at',
            ])
            ->orderByDesc('created_at');
    }
}

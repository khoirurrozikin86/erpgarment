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
                'images:id,product_id,path,file_name,is_primary,sort_order',
                'primaryImage:id,product_id,path,file_name,is_primary,sort_order',
                'stock:product_id,quantity',
            ])
            ->select([
                'id',
                'code',
                'barcode',
                'sku',
                'name',
                'short_name',
                'category_id',
                'unit_id',
                'product_type',
                'purchase_price',
                'sales_price',
                'min_stock',
                'max_stock',
                'reorder_point',
                'track_stock',
                'taxable',
                'tax_rate',
                'allow_discount',
                'allow_purchase',
                'allow_sales',
                'description',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->orderByDesc('created_at');
    }
}

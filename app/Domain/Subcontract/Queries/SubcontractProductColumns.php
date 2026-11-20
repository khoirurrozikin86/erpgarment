<?php

namespace App\Domain\Subcontract\Queries;

use App\Models\Product;

final class SubcontractProductColumns
{
    public const RELATIONS = ['parentProduct:id,code', 'category:id,name'];
    public const HEADINGS = [
        'parent_code' => 'variants.stock_parent_code',
        'sku_code' => 'variants.stock_sku',
        'name' => 'ui.common.item_name',
        'category_name' => 'ui.common.category',
        'color' => 'ui.workflow.color',
        'size' => 'ui.workflow.size',
    ];

    public static function values(?Product $product, ?string $color = null, ?string $size = null): array
    {
        $variant = filled($product?->parent_product_id);

        return [
            'parent_code' => $product?->parentProduct?->code ?? $product?->code,
            'sku_code' => $variant ? $product->code : null,
            'name' => $product?->name,
            'category_name' => $product?->category?->name,
            'color' => filled($color) ? $color : ($variant ? $product->color : null),
            'size' => filled($size) ? $size : ($variant ? $product->size : null),
        ];
    }
}

<?php

namespace App\Domain\Purchasing\Queries;

use App\Models\MaterialIssueItem;
use App\Models\Product;

final class GoodsTraceItemColumns
{
    public static function headings(): array
    {
        return [
            __('variants.stock_parent_code'),
            __('variants.stock_sku'),
            __('bc_reports.item_name'),
            __('ui.common.category'),
            __('ui.workflow.color'),
            __('ui.workflow.size'),
        ];
    }

    public static function values(?Product $product, ?string $color = null, ?string $size = null): array
    {
        return [
            $product?->parentProduct?->code ?? $product?->code,
            $product?->parent_product_id ? $product->code : null,
            $product?->name,
            $product?->category?->name,
            filled($color) ? $color : ($product?->parent_product_id ? $product->color : null),
            filled($size) ? $size : ($product?->parent_product_id ? $product->size : null),
        ];
    }

    public static function issued(MaterialIssueItem $item): array
    {
        return self::values(
            $item->product,
            $item->rawMaterialOrderItem?->color ?: $item->rawMaterialOrderItem?->sourceMaterial?->color ?: $item->requestItem?->color,
            $item->rawMaterialOrderItem?->size ?: $item->rawMaterialOrderItem?->sourceMaterial?->size ?: $item->requestItem?->size,
        );
    }
}

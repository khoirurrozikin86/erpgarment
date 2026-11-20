<?php

namespace App\Domain\Inventory\Services;

use App\Models\Product;
use App\Models\ProductionOrderMaterial;
use App\Models\RawMaterialOrderItem;

class MaterialIssueStockSku
{
    public function resolve(RawMaterialOrderItem $source, ?Product $product, ?ProductionOrderMaterial $material): ?Product
    {
        if (!$product?->track_stock) {
            return null;
        }

        $color = filled($source->color) ? $source->color : $material?->color;
        $size = filled($source->size) ? $source->size : $material?->size;
        $root = $product->parentProduct ?? $product;
        $dimensions = $this->dimensions($color, $size);

        if ($dimensions === ['', '']) {
            $sku = $product->parent_product_id || $root->variants->isEmpty() ? $product : null;
        } elseif ($dimensions === $this->dimensions($root->color, $root->size)) {
            $sku = $root;
        } else {
            $sku = $root->variants->firstWhere('variant_key', hash('sha256', json_encode($dimensions)));
        }

        return $sku?->track_stock && $sku->is_active ? $sku : null;
    }

    private function dimensions(?string $color, ?string $size): array
    {
        return [mb_strtolower(trim($color ?? '')), mb_strtolower(trim($size ?? ''))];
    }
}

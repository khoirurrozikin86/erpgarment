<?php

namespace App\Domain\Products\Services;

use App\Domain\Products\Actions\CreateProductAction;
use App\Domain\Products\DTOs\ProductData;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductVariantService
{
    public function forStockMovement(Product $product, ?string $color, ?string $size): Product
    {
        if (!$product->track_stock || (blank($color) && blank($size))) {
            return $product;
        }

        return $this->resolve($product->id, $color ?? '', $size);
    }

    // Caller owns the transaction: lock the root before looking up or creating children.
    public function resolve(int $productId, string $color, ?string $size): Product
    {
        $selected = Product::findOrFail($productId);
        $parent = Product::lockForUpdate()->findOrFail($selected->parent_product_id ?: $selected->id);
        $color = trim($color);
        $size = trim($size ?? '');
        $size = $size === '' ? null : $size;
        if (!$parent->is_active || $parent->product_type !== 'stock') {
            throw ValidationException::withMessages(['output_product_id' => __('variants.invalid_parent')]);
        }
        $dimensions = [mb_strtolower($color), mb_strtolower($size ?? '')];
        if (($parent->color || $parent->size) && $dimensions === [mb_strtolower(trim($parent->color ?? '')), mb_strtolower(trim($parent->size ?? ''))]) {
            return $parent;
        }
        $key = hash('sha256', json_encode($dimensions));
        $variant = $parent->variants()->where('variant_key', $key)->first();
        if ($variant) {
            if (!$variant->is_active) throw ValidationException::withMessages(['order_color' => __('variants.inactive')]);
            return $variant;
        }
        $suffix = strtoupper(substr(Str::slug($color . '-' . $size), 0, 15));
        $attributes = $parent->only([
            'name', 'category_id', 'unit_id', 'product_type', 'garment_item_type',
            'purchase_price', 'min_stock', 'max_stock', 'reorder_point', 'track_stock', 'is_available',
        ]);
        $attributes += [
            'code' => substr($parent->code, 0, 24) . '-' . $suffix . '-' . strtoupper(substr($key, 0, 8)),
            'parent_product_id' => $parent->id, 'color' => $color, 'size' => $size, 'is_active' => true,
        ];
        return app(CreateProductAction::class)(ProductData::fromArray($attributes));
    }
}

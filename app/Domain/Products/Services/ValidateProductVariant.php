<?php

namespace App\Domain\Products\Services;

use App\Domain\Products\DTOs\ProductData;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class ValidateProductVariant
{
    public function attributes(ProductData $data, ?Product $product = null): array
    {
        $attributes = $data->toArray();
        $attributes['color'] = trim($data->color ?? '') ?: null;
        $attributes['size'] = trim($data->size ?? '') ?: null;
        $attributes['variant_key'] = null;
        if ($product && $product->variants()->exists()
            && ((int) $product->unit_id !== (int) $data->unitId || $product->product_type !== $data->productType)) {
            throw ValidationException::withMessages(['unit_id' => __('variants.same_unit')]);
        }
        if ($data->parentProductId) {
            $parent = Product::query()->lockForUpdate()->findOrFail($data->parentProductId);
            if ($parent->parent_product_id || $parent->id === $product?->id || $product?->variants()->exists()) {
                throw ValidationException::withMessages(['parent_product_id' => __('variants.invalid_parent')]);
            }
            if (!$attributes['color'] && !$attributes['size']) {
                throw ValidationException::withMessages(['color' => __('variants.required_dimensions')]);
            }
            if ((int) $parent->unit_id !== (int) $data->unitId || $parent->product_type !== $data->productType) {
                throw ValidationException::withMessages(['parent_product_id' => __('variants.same_unit')]);
            }
            $attributes['variant_key'] = hash('sha256', json_encode([mb_strtolower($attributes['color'] ?? ''), mb_strtolower($attributes['size'] ?? '')]));
            if (Product::where('parent_product_id', $parent->id)->where('variant_key', $attributes['variant_key'])
                ->when($product, fn ($query) => $query->whereKeyNot($product->id))->exists()) {
                throw ValidationException::withMessages(['color' => __('variants.duplicate')]);
            }
        }
        if ($product && ($product->stockMovements()->exists() || $product->billOfMaterialComponents()->exists() || $product->billsOfMaterial()->exists()
            || \App\Models\ProductionOrder::where('output_product_id', $product->id)->exists()
            || \App\Models\MaterialRequestItem::where('product_id', $product->id)->exists())) {
            foreach (['parent_product_id', 'color', 'size'] as $field) {
                if ((string) $product->$field !== (string) $attributes[$field]) {
                    throw ValidationException::withMessages([$field => __('variants.used_identity')]);
                }
            }
        }
        return $attributes;
    }
}

<?php

namespace App\Domain\Manufacturing\Services;

use App\Models\Product;
use App\Models\VariantDimension;
use Illuminate\Support\Collection;

class RawMaterialOrderGroups
{
    private const TYPE_ORDER = ['fabric' => 0, 'accessory' => 1, 'general' => 2];

    private ?Collection $sizeOrder = null;

    public function itemCodes(?Product $product, ?string $color, ?string $size): array
    {
        $sku = null;
        if ($product?->parent_product_id) {
            $sku = $product;
        } elseif ($product && (filled($color) || filled($size))) {
            $dimensions = [mb_strtolower(trim($color ?? '')), mb_strtolower(trim($size ?? ''))];
            $sku = $product->variants->firstWhere('variant_key', hash('sha256', json_encode($dimensions)));
        }

        return [
            'parent_code' => $product?->parentProduct?->code ?? $product?->code,
            'sku_id' => $sku?->id,
            'sku_code' => $sku?->code,
        ];
    }

    public function group(Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return collect();
        }

        $normalize = fn ($value) => mb_strtolower(trim($value ?? ''));
        $sizeOrder = $this->sizeOrder ??= VariantDimension::options('size')->pluck('code')
            ->mapWithKeys(fn ($code, $index) => [$normalize($code) => $index]);

        return $items->groupBy(fn (array $item) => json_encode([
            $item['sku_id'] ?? $item['product_id'] ?? $item['source_id'],
            $normalize($item['color']), $normalize($item['size']), $normalize($item['unit']),
        ]))->map(function (Collection $sources) {
            $categories = $sources->pluck('category')->filter()->unique()
                ->sortBy(fn ($category) => self::TYPE_ORDER[$category] ?? PHP_INT_MAX)->values();

            return [
                ...$sources->first(),
                'categories' => $categories->all(),
                'quantity' => round($sources->sum('quantity'), 4),
                'sources' => $sources->values()->all(),
            ];
        })->sort(function (array $a, array $b) use ($normalize, $sizeOrder) {
            $sizeRank = fn (array $item) => $normalize($item['size']) === ''
                ? -1 : $sizeOrder->get($normalize($item['size']), PHP_INT_MAX);

            return (self::TYPE_ORDER[$a['categories'][0] ?? ''] ?? PHP_INT_MAX) <=> (self::TYPE_ORDER[$b['categories'][0] ?? ''] ?? PHP_INT_MAX)
                ?: strnatcasecmp($a['parent_code'] ?? '', $b['parent_code'] ?? '')
                ?: strnatcasecmp($normalize($a['color']), $normalize($b['color']))
                ?: $sizeRank($a) <=> $sizeRank($b)
                ?: strnatcasecmp($normalize($a['size']), $normalize($b['size']));
        })->values();
    }
}

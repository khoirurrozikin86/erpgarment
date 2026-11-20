<?php

namespace App\Domain\Inventory\Queries;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StockFamilyPage
{
    public function get(int $start, int $length, string $search, bool $searchDimensions = false): array
    {
        $parents = Product::query()->whereNull('parent_product_id')
            ->where(fn (Builder $items) => $items->where('track_stock', true)
                ->orWhereHas('variants', fn (Builder $variants) => $variants->where('track_stock', true)));
        $total = $parents->count();
        $filtered = $total;

        if ($search !== '') {
            $pattern = '%'.$search.'%';
            $matchesItem = fn (Builder $items) => $items->where('code', 'like', $pattern)->orWhere('name', 'like', $pattern)
                ->when($searchDimensions, fn (Builder $dimensions) => $dimensions
                    ->orWhere('color', 'like', $pattern)->orWhere('size', 'like', $pattern));
            $parents->where(fn (Builder $items) => $items->where($matchesItem)
                ->orWhereHas('variants', fn (Builder $variants) => $variants->where('track_stock', true)->where($matchesItem)));
            $filtered = $parents->count();
        }

        return [
            'total' => $total,
            'filtered' => $filtered,
            'ids' => $parents->orderBy('code')->orderBy('id')->offset($start)->limit($length)->pluck('id'),
        ];
    }

    public function productGroups(array $parentIds): Collection
    {
        $products = Product::query()
            ->where('track_stock', true)
            ->where(fn (Builder $query) => $query->whereKey($parentIds)->orWhereIn('parent_product_id', $parentIds))
            ->with(['unit:id,name,symbol', 'parentProduct:id,code,name'])
            ->select(['id', 'code', 'name', 'unit_id', 'track_stock', 'parent_product_id', 'color', 'size'])
            ->orderBy('color')->orderBy('size')->orderBy('code')->get()
            ->groupBy(fn (Product $product) => $product->parent_product_id ?? $product->id);

        return collect($parentIds)->map(fn (int $id) => $products->get($id))->filter()->values();
    }
}

<?php

namespace App\Domain\Inventory\Queries;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockBalanceQuery
{
    public function builder(): Builder
    {
        return Product::query()
            ->where('track_stock', true)
            ->with(['category:id,name', 'unit:id,name,symbol', 'stock:id,product_id,quantity', 'parentProduct:id,code,name,min_stock,reorder_point'])
            ->with(['stockMovements' => fn ($movements) => $movements->withTransactionDimensions()])
            ->select([
                'id', 'code', 'name', 'category_id', 'unit_id', 'parent_product_id',
                'min_stock', 'max_stock', 'reorder_point', 'track_stock',
            ])
            ->addSelect(['global_quantity' => DB::table('product_stocks as balances')
                ->join('products as family', 'family.id', '=', 'balances.product_id')
                ->where(fn ($family) => $family
                    ->whereRaw('family.id = COALESCE(products.parent_product_id, products.id)')
                    ->orWhereRaw('family.parent_product_id = COALESCE(products.parent_product_id, products.id)'))
                ->selectRaw('SUM(balances.quantity)')])
            ->orderBy('name');
    }

    public function groups(array $parentIds): Collection
    {
        $productsByParent = $this->builder()->where(fn (Builder $query) => $query
            ->whereKey($parentIds)->orWhereIn('parent_product_id', $parentIds))
            ->orderBy('code')->get()
            ->groupBy(fn (Product $product) => $product->parent_product_id ?? $product->id);

        return collect($parentIds)->map(function (int $id) use ($productsByParent) {
            $products = $productsByParent->get($id);
            if (!$products) {
                return null;
            }

            $first = $products->first();
            $parent = $first->parentProduct ?? $first;
            $quantity = (float) ($first->global_quantity ?? 0);
            $threshold = $this->threshold($parent);
            $details = $products->map(function (Product $product) {
                $quantity = (float) ($product->stock?->quantity ?? 0);
                $threshold = $this->threshold($product);

                return [
                    'id' => $product->id, 'code' => $product->code, 'name' => $product->name,
                    ...StockMovementDimensions::summarize($product->stockMovements),
                    'quantity' => $quantity, 'threshold' => $threshold,
                    'status' => $this->status($quantity, $threshold),
                ];
            });

            return [
                'code' => $parent->code, 'name' => $parent->name, 'category' => $first->category?->name,
                'unit' => $first->unit?->symbol ?: $first->unit?->name,
                'quantity' => $quantity, 'threshold' => $threshold,
                'status' => $this->status($quantity, $threshold),
                'details' => $details->reject(fn (array $sku) => $details->count() > 1
                    && $sku['id'] === $id && $sku['quantity'] === 0.0
                    && $sku['color'] === '—' && $sku['size'] === '—')->values(),
            ];
        })->filter()->values();
    }

    private function threshold(Product $product): float
    {
        return (float) ($product->reorder_point > 0 ? $product->reorder_point : $product->min_stock);
    }

    private function status(float $quantity, float $threshold): string
    {
        return match (true) {
            $quantity <= 0 => 'out_of_stock',
            $threshold > 0 && $quantity <= $threshold => 'low_stock',
            default => 'safe_stock',
        };
    }
}

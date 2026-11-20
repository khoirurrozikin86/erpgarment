<?php

namespace App\Domain\Inventory\Queries;

use App\Models\Product;
use App\Models\StockMovement;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class InventoryReportQuery
{
    public function builder(string $fromDate, string $toDate): Builder
    {
        $from = CarbonImmutable::parse($fromDate)->startOfDay();
        $until = CarbonImmutable::parse($toDate)->addDay()->startOfDay();

        return Product::query()->where('track_stock', true)
            ->with([
                'category:id,name', 'unit:id,name,symbol',
                'parentProduct:id,code,name,min_stock,reorder_point',
                'stockMovements' => fn ($movements) => $movements->withTransactionDimensions()->where('created_at', '<', $until),
            ])
            ->select(['id', 'code', 'name', 'category_id', 'unit_id', 'parent_product_id', 'min_stock', 'reorder_point'])
            ->addSelect([
                'opening_balance' => StockMovement::query()->select('quantity_after')
                    ->whereColumn('product_id', 'products.id')->where('created_at', '<', $from)
                    ->orderByDesc('created_at')->orderByDesc('id')->limit(1),
            ])
            ->withSum(['stockMovements as quantity_in' => fn (Builder $query) => $query
                ->where('created_at', '>=', $from)->where('created_at', '<', $until)->where('quantity', '>', 0)], 'quantity')
            ->withSum(['stockMovements as quantity_out' => fn (Builder $query) => $query
                ->where('created_at', '>=', $from)->where('created_at', '<', $until)->where('quantity', '<', 0)], 'quantity')
            ->orderBy('name')->orderBy('code');
    }

    public function summary(string $fromDate, string $toDate): array
    {
        $balances = $this->builder($fromDate, $toDate)->withoutEagerLoads()->reorder();
        $parents = DB::query()->fromSub($balances, 'balances')
            ->join('products as parents', fn ($join) => $join->whereRaw('parents.id = COALESCE(balances.parent_product_id, balances.id)'))
            ->groupBy('parents.id', 'parents.reorder_point', 'parents.min_stock')
            ->selectRaw('SUM(COALESCE(opening_balance, 0) + COALESCE(quantity_in, 0) + COALESCE(quantity_out, 0)) as closing')
            ->selectRaw('SUM(COALESCE(quantity_in, 0) - COALESCE(quantity_out, 0)) as activity')
            ->selectRaw('CASE WHEN parents.reorder_point > 0 THEN parents.reorder_point ELSE COALESCE(parents.min_stock, 0) END as threshold');
        $counts = DB::query()->fromSub($parents, 'inventory_totals')
            ->selectRaw('COUNT(*) as products')
            ->selectRaw('SUM(CASE WHEN activity > 0 THEN 1 ELSE 0 END) as moved_products')
            ->selectRaw('SUM(CASE WHEN threshold > 0 AND closing <= 0 THEN 1 ELSE 0 END) as out_of_stock')
            ->selectRaw('SUM(CASE WHEN threshold > 0 AND closing > 0 AND closing <= threshold THEN 1 ELSE 0 END) as low_stock')
            ->first();

        $summary = array_map('intval', (array) $counts);
        $summary['safe_stock'] = $summary['products'] - $summary['out_of_stock'] - $summary['low_stock'];
        return $summary;
    }

    public function groups(string $fromDate, string $toDate, ?array $parentIds = null): Collection
    {
        $products = $this->builder($fromDate, $toDate)
            ->when($parentIds !== null, fn (Builder $query) => $query->where(fn (Builder $items) => $items
                ->whereKey($parentIds)->orWhereIn('parent_product_id', $parentIds)))
            ->get();

        return $products->groupBy(fn (Product $product) => $product->parent_product_id ?? $product->id)
            ->map(function ($products) {
                $first = $products->first();
                $parent = $first->parentProduct ?? $first;
                $details = $products->map(function (Product $product) {
                    $opening = (float) ($product->opening_balance ?? 0);
                    $incoming = (float) ($product->quantity_in ?? 0);
                    $outgoing = abs((float) ($product->quantity_out ?? 0));
                    $closing = round($opening + $incoming - $outgoing, 4);
                    $threshold = $this->threshold($product);

                    return [
                        'id' => $product->id, 'code' => $product->code,
                        ...StockMovementDimensions::summarize($product->stockMovements),
                        'opening' => $opening, 'incoming' => $incoming, 'outgoing' => $outgoing, 'closing' => $closing,
                        'threshold' => $threshold, 'status' => $this->status($closing, $threshold),
                    ];
                });
                $closing = round($details->sum('closing'), 4);

                return [
                    'id' => $parent->id,
                    'code' => $parent->code, 'name' => $parent->name,
                    'category' => $first->category?->name,
                    'unit' => $first->unit?->symbol ?: $first->unit?->name,
                    'opening' => $details->sum('opening'), 'incoming' => $details->sum('incoming'),
                    'outgoing' => $details->sum('outgoing'), 'closing' => $closing,
                    'status' => $this->status($closing, $this->threshold($parent)),
                    'details' => $details->reject(fn (array $sku) => $details->count() > 1
                        && $sku['id'] === $parent->id && !$sku['opening'] && !$sku['incoming'] && !$sku['outgoing']
                        && $sku['color'] === '—' && $sku['size'] === '—')->values(),
                ];
            })->sortBy('code', SORT_NATURAL)->values();
    }

    private function threshold(Product $product): float
    {
        return (float) ($product->reorder_point > 0 ? $product->reorder_point : $product->min_stock);
    }

    private function status(float $balance, float $threshold): string
    {
        return match (true) {
            $threshold > 0 && $balance <= 0 => 'out_of_stock',
            $threshold > 0 && $balance <= $threshold => 'low_stock',
            default => 'safe_stock',
        };
    }
}

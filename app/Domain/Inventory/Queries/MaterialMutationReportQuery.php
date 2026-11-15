<?php

namespace App\Domain\Inventory\Queries;

use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MaterialMutationReportQuery
{
    public function builder(array $filters): Builder
    {
        $from = CarbonImmutable::parse($filters['from_date'])->startOfDay();
        $until = CarbonImmutable::parse($filters['to_date'])->addDay()->startOfDay();
        $query = $this->products($filters)
            ->when($filters['search'] ?? null, fn (Builder $q, string $search) => $q->where(fn (Builder $q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
            ->withSum(['stockMovements as adjustment_in' => fn (Builder $q) => $q->where('created_at', '>=', $from)->where('created_at', '<', $until)->where('movement_type', 'stock_adjustment')->where('quantity', '>', 0)], 'quantity')
            ->withSum(['stockMovements as adjustment_out' => fn (Builder $q) => $q->where('created_at', '>=', $from)->where('created_at', '<', $until)->where('movement_type', 'stock_adjustment')->where('quantity', '<', 0)], 'quantity');

        // Use one deterministic posted count per product, including genuine zero counts.
        $count = DB::table('stock_opname_items as oi')->join('stock_opnames as o', 'o.id', '=', 'oi.stock_opname_id')
            ->whereColumn('oi.product_id', 'products.id')->where('o.status', 'posted')
            ->where('o.posted_at', '>=', $from)->where('o.posted_at', '<', $until)
            ->orderByDesc('o.posted_at')->orderByDesc('o.id')->orderByDesc('oi.id')->limit(1);
        return $query->addSelect([
            'counted_quantity' => (clone $count)->select('oi.counted_quantity'),
            'count_difference' => (clone $count)->select('oi.difference'),
            'count_number' => (clone $count)->select('o.number'),
        ])->reorder('code')->orderBy('id');
    }

    protected function products(array $filters): Builder
    {
        return app(InventoryReportQuery::class)->builder($filters['from_date'], $filters['to_date'])
            ->where('product_type', 'stock')
            ->where(function (Builder $q) {
                $q->whereIn('garment_item_type', ['fabric', 'accessory', 'packaging'])
                    ->orWhereHas('category', fn (Builder $q) => $q->whereIn('code', ['RAW', 'FAB', 'ACC', 'PACK']))
                    ->orWhere(function (Builder $q) {
                        $q->where('garment_item_type', 'general')->whereHas('billOfMaterialComponents');
                    });
            });
    }

    public static function values(Product $product): array
    {
        $opening = (float) ($product->opening_balance ?? 0);
        $adjustmentIn = (float) ($product->adjustment_in ?? 0);
        $adjustmentOut = (float) ($product->adjustment_out ?? 0);
        $incoming = (float) ($product->quantity_in ?? 0) - $adjustmentIn;
        $outgoing = abs((float) ($product->quantity_out ?? 0) - $adjustmentOut);
        $adjustment = $adjustmentIn + $adjustmentOut;
        return [
            'opening' => $opening, 'incoming' => $incoming, 'outgoing' => $outgoing,
            'adjustment' => $adjustment, 'closing' => round($opening + $incoming - $outgoing + $adjustment, 4),
            'counted' => $product->counted_quantity === null ? null : (float) $product->counted_quantity,
            'difference' => $product->count_difference === null ? null : (float) $product->count_difference,
            'notes' => $product->count_number ? __('bc_reports.stock_count').$product->count_number : null,
        ];
    }
}

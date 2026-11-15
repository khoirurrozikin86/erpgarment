<?php

namespace App\Domain\Inventory\Queries;

use App\Models\StockMovement;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class FinishedGoodsReturnReportQuery
{
    public function builder(array $filters): Builder
    {
        $from = CarbonImmutable::parse($filters['from_date'])->startOfDay();
        $until = CarbonImmutable::parse($filters['to_date'])->addDay()->startOfDay();
        return StockMovement::query()->where('movement_type', 'customer_return')->where('quantity', '>', 0)
            ->where('created_at', '>=', $from)->where('created_at', '<', $until)
            ->whereHas('product', fn (Builder $q) => $q->where('track_stock', true)->where(function (Builder $q) {
                $q->where('garment_item_type', 'finished_good')->orWhereHas('category', fn (Builder $q) => $q->where('code', 'FG')->orWhereRaw('LOWER(name) = ?', ['finished goods']));
            }))
            ->with('product.unit')
            ->when($filters['search'] ?? null, fn (Builder $q, string $search) => $q->whereHas('product', fn (Builder $q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
            ->orderBy('created_at')->orderBy('id');
    }
}

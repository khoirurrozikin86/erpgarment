<?php

namespace App\Domain\Inventory\Queries;

use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use App\Models\StockOpname;
use Illuminate\Database\Eloquent\Builder;

class StockOpnameTableQuery
{
    use AppliesDateRange;

    public function builder(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = StockOpname::query()
            ->with(['starter:id,name', 'counter:id,name', 'poster:id,name'])
            ->withCount('items')
            ->orderByDesc('counted_at');

        return $this->applyDateRange($query, 'counted_at', $fromDate, $toDate);
    }

    public function details(StockOpname $stockOpname): StockOpname
    {
        return StockOpname::query()
            ->with([
                'starter:id,name', 'counter:id,name', 'poster:id,name',
                'items.product:id,code,name,unit_id,parent_product_id,color,size',
                'items.product.unit:id,name,symbol', 'items.product.parentProduct:id,code',
            ])
            ->findOrFail($stockOpname->id);
    }
}

<?php

namespace App\Domain\PriceLists\Queries;

use App\Models\PriceList;
use Illuminate\Database\Eloquent\Builder;

class PriceListTableQuery
{
    public function builder(): Builder
    {
        return PriceList::query()
            ->with(['customer:id,name', 'supplier:id,name', 'items'])
            ->select([
                'id', 'code', 'name', 'type', 'customer_id', 'supplier_id',
                'valid_from', 'valid_until', 'is_active', 'notes', 'created_at', 'updated_at',
            ])
            ->withCount('items')
            ->orderByDesc('created_at');
    }
}

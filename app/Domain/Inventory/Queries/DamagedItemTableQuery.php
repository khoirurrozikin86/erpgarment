<?php

namespace App\Domain\Inventory\Queries;

use App\Models\DamagedItemLine;
use Illuminate\Database\Eloquent\Builder;

class DamagedItemTableQuery
{
    public function builder(): Builder
    {
        return DamagedItemLine::query()->with(['document:id,number,document_date', 'product:id,code,name,unit_id', 'product.unit:id,name,symbol'])->select('damaged_item_lines.*')->latest('damaged_item_lines.created_at');
    }

    public function products(): Builder
    {
        return \App\Models\Product::query()->where('track_stock', true)->where('is_active', true)
            ->with('unit:id,name,symbol')->select(['id', 'code', 'name', 'unit_id'])->orderBy('name');
    }
}

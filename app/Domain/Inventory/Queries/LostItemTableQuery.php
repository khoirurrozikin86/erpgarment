<?php

namespace App\Domain\Inventory\Queries;

use App\Models\LostItemLine;
use Illuminate\Database\Eloquent\Builder;

class LostItemTableQuery
{
    public function builder(): Builder
    {
        return LostItemLine::query()->with(['document:id,number,document_date', 'product:id,code,name,unit_id', 'product.unit:id,name,symbol'])->select('lost_item_lines.*')->latest('lost_item_lines.created_at');
    }

    public function products(): Builder
    {
        return \App\Models\Product::query()->where('track_stock', true)->where('is_active', true)
            ->with('unit:id,name,symbol')->select(['id', 'code', 'name', 'unit_id'])->orderBy('name');
    }
}

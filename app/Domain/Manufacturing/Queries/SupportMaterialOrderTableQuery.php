<?php
namespace App\Domain\Manufacturing\Queries;

use App\Models\{Product, SupportMaterialOrderItem};
use Illuminate\Database\Eloquent\Builder;

class SupportMaterialOrderTableQuery
{
    public function builder(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return SupportMaterialOrderItem::query()
            ->whereHas('document', fn (Builder $q) => $q->where('company_id', $companyId)->when($from, fn ($q) => $q->whereDate('order_date', '>=', $from))->when($to, fn ($q) => $q->whereDate('order_date', '<=', $to)))
            ->with(['document:id,company_id,number,order_date', 'product:id,code,name,unit_id', 'product.unit:id,name,symbol'])
            ->select('support_material_order_items.*')->latest('support_material_order_items.created_at');
    }

    public function products(): Builder
    {
        return Product::query()->where('is_active', true)->with('unit:id,name,symbol')->select(['id','code','name','unit_id'])->orderBy('name');
    }
}

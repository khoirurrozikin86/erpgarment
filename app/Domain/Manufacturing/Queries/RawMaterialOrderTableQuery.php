<?php

namespace App\Domain\Manufacturing\Queries;

use App\Models\{ProductionOrder, ProductionOrderMaterial, RawMaterialOrderItem};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RawMaterialOrderTableQuery
{
    public function rows(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return RawMaterialOrderItem::query()
            ->with(['document:id,company_id,number,order_date,production_order_number', 'product:id,code,name,unit_id', 'product.unit:id,name,symbol'])
            ->whereHas('document', fn (Builder $q) => $q->where('company_id', $companyId)->when($from, fn ($q) => $q->whereDate('order_date', '>=', $from))->when($to, fn ($q) => $q->whereDate('order_date', '<=', $to)))
            ->select('raw_material_order_items.*')
            ->latest('raw_material_order_items.created_at');
    }

    public function moGroups(int $companyId): Builder
    {
        $firstRows = ProductionOrder::query()->selectRaw('MIN(id)')->where('company_id', $companyId)->groupBy('number');

        return ProductionOrder::query()->where('company_id', $companyId)->whereIn('id', $firstRows)
            ->with('outputProduct:id,code,name,style_code')->withCount('materials')
            ->select(['id', 'number', 'po_date', 'output_product_id'])->orderByDesc('po_date');
    }

    public function materialsForMo(int $companyId, string $number): Collection
    {
        return ProductionOrderMaterial::query()->whereHas('productionOrder', fn (Builder $q) => $q->where('company_id', $companyId)->where('number', $number))
            ->with(['productionOrder.outputProduct:id,style_code', 'product:id,code,name,unit_id', 'product.unit:id,name,symbol'])
            ->orderBy('production_order_id')->orderBy('id')->get();
    }
}

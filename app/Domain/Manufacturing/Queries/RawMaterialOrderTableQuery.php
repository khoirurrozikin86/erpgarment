<?php

namespace App\Domain\Manufacturing\Queries;

use App\Models\{ProductionOrder, ProductionOrderMaterial, RawMaterialOrderDocument, RawMaterialOrderItem};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RawMaterialOrderTableQuery
{
    public function documents(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return RawMaterialOrderDocument::query()
            ->where('company_id', $companyId)
            ->with([
                'items.product.unit',
                'items.product.parentProduct:id,code',
                'items.product.variants:id,parent_product_id,code,variant_key',
                'items.sourceMaterial.billOfMaterial:id,bom_type',
            ])
            ->when($from, fn (Builder $q) => $q->whereDate('order_date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('order_date', '<=', $to))
            ->orderByDesc('order_date')
            ->orderByDesc('id');
    }

    public function rows(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return RawMaterialOrderItem::query()
            ->with([
                'document:id,company_id,number,order_date,production_order_number',
                'sourceMaterial:id,color,size',
                'product:id,code,name,unit_id',
                'product.unit:id,name,symbol',
            ])
            ->whereHas('document', fn (Builder $q) => $q->where('company_id', $companyId)->when($from, fn ($q) => $q->whereDate('order_date', '>=', $from))->when($to, fn ($q) => $q->whereDate('order_date', '<=', $to)))
            ->select('raw_material_order_items.*')
            ->latest('raw_material_order_items.created_at');
    }

    public function moGroups(int $companyId): Builder
    {
        $firstRows = ProductionOrder::query()->selectRaw('MIN(id)')
            ->where('company_id', $companyId)
            ->groupBy('number');

        return ProductionOrder::query()->where('company_id', $companyId)
            ->whereIn('id', $firstRows)
            ->with('outputProduct:id,code,name,style_code,color,size')
            ->withCount('materials')
            ->select(['id', 'number', 'po_date', 'output_product_id', 'planned_quantity', 'style_code'])
            ->orderByDesc('po_date')
            ->orderByDesc('id');
    }

    public function materialsForMo(int $companyId, string $number, string $category = 'all'): Collection
    {
        return ProductionOrderMaterial::query()->whereHas('productionOrder', fn (Builder $q) => $q->where('company_id', $companyId)->where('number', $number))
            ->fromActiveBom($companyId, $category)
            ->with([
                'billOfMaterial:id,bom_type', 'productionOrder.billOfMaterial:id,bom_type', 'productionOrder.outputProduct:id,style_code',
                'product:id,code,name,unit_id,garment_item_type,parent_product_id,color,size', 'product.unit:id,name,symbol',
                'product.parentProduct:id,code', 'product.variants:id,parent_product_id,code,variant_key',
            ])
            ->orderBy('production_order_id')->orderBy('id')->get();
    }
}

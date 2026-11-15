<?php

namespace App\Domain\Subcontract\Queries;

use App\Models\{Product, ProductionOrder, SubcontractSemiFinishedItem};
use Illuminate\Database\Eloquent\Builder;

class SubcontractSemiFinishedTableQuery
{
    public function builder(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return SubcontractSemiFinishedItem::query()
            ->whereHas('document', fn (Builder $q) => $q->where('company_id', $companyId)
                ->when($from, fn (Builder $q) => $q->whereDate('production_date', '>=', $from))
                ->when($to, fn (Builder $q) => $q->whereDate('production_date', '<=', $to)))
            ->with([
                'document:id,company_id,number,production_date,production_order_id,buyer_po_number,description',
                'document.productionOrder:id,number,buyer_po_number',
                'product:id,code,name,unit_id,style_code',
                'product.unit:id,name,symbol',
            ])
            ->select('subcontract_semi_finished_items.*')->latest('subcontract_semi_finished_items.id');
    }

    public function products(): Builder
    {
        return Product::query()->where('track_stock', true)->where('is_active', true)
            ->where(function (Builder $query) {
                $query->whereHas('category', fn (Builder $category) => $category->where('code', 'WIP')->orWhereRaw('LOWER(name) = ?', ['barang setengah jadi']))
                    ->orWhere('garment_item_type', 'work_in_process');
            })
            ->with('unit:id,name,symbol')->select(['id', 'code', 'name', 'unit_id', 'category_id', 'style_code'])->orderBy('name');
    }

    public function productionOrders(int $companyId): Builder
    {
        return ProductionOrder::query()->where('company_id', $companyId)
            ->with('outputProduct:id,code,name')->select(['id', 'number', 'buyer_po_number', 'output_product_id', 'planned_quantity'])
            ->latest('id');
    }
}

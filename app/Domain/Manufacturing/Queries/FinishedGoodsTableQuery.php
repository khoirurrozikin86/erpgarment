<?php

namespace App\Domain\Manufacturing\Queries;

use App\Models\ProductionOrder;
use App\Models\ProductionOrderStageEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FinishedGoodsTableQuery
{
    public function builder(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return ProductionOrderStageEntry::query()->where('stage', 'finishing')
            ->whereDate('processed_at', '>=', $from ?: '1000-01-01')->whereDate('processed_at', '<=', $to ?: '9999-12-31')
            ->whereHas('productionOrder', fn (Builder $q) => $q->where('company_id', $companyId))
            ->with(['productionOrder:id,company_id,number,buyer_po_number,no_cbd,output_product_id', 'productionOrder.outputProduct:id,code,name,style_code,unit_id', 'productionOrder.outputProduct.unit:id,name,symbol', 'processor:id,name,username'])
            ->select('production_order_stage_entries.*')->latest('processed_at');
    }

    public function eligibleOrders(int $companyId): Builder
    {
        $packing = DB::table('production_order_stage_entries')->selectRaw('production_order_id,SUM(good_quantity) packing_qty')
            ->where('stage', 'packing')->whereNull('deleted_at')->groupBy('production_order_id');
        $received = DB::table('production_order_stage_entries')->selectRaw('production_order_id,SUM(input_quantity) received_qty')
            ->where('stage', 'finishing')->whereNull('deleted_at')->groupBy('production_order_id');
        return ProductionOrder::query()->where('production_orders.company_id', $companyId)->where('production_orders.status', 'in_progress')
            ->joinSub($packing, 'packing_stage', fn ($j) => $j->on('packing_stage.production_order_id', '=', 'production_orders.id'))
            ->leftJoinSub($received, 'finished_stage', fn ($j) => $j->on('finished_stage.production_order_id', '=', 'production_orders.id'))
            ->whereRaw('COALESCE(packing_stage.packing_qty,0)>COALESCE(finished_stage.received_qty,0)')
            ->with(['outputProduct:id,code,name,style_code,unit_id', 'outputProduct.unit:id,name,symbol'])
            ->select(['production_orders.id', 'production_orders.company_id', 'production_orders.number', 'production_orders.buyer_po_number', 'production_orders.no_cbd', 'production_orders.output_product_id'])
            ->selectRaw('COALESCE(packing_stage.packing_qty,0)-COALESCE(finished_stage.received_qty,0) as available_packing_quantity')
            ->latest('production_orders.created_at');
    }
}

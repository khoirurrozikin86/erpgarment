<?php
namespace App\Domain\Manufacturing\Queries;

use App\Models\{ProductionOrder,ProductionOrderStageEntry};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SewingTableQuery
{
    public function builder(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return ProductionOrderStageEntry::query()->where('stage', 'sewing')
            ->whereDate('processed_at', '>=', $from ?: '1000-01-01')->whereDate('processed_at', '<=', $to ?: '9999-12-31')
            ->whereHas('productionOrder', fn (Builder $q) => $q->where('company_id', $companyId))
            ->with(['productionOrder:id,company_id,number,buyer_po_number,output_product_id', 'productionOrder.stageEntries' => fn ($q) => $q->where('stage', 'cutting')->select('id','production_order_id','good_quantity'), 'productionOrder.outputProduct:id,code,name,style_code,unit_id', 'productionOrder.outputProduct.unit:id,name,symbol', 'processor:id,name,username', 'materialProduct:id,code,name,unit_id', 'materialProduct.unit:id,name,symbol'])
            ->select('production_order_stage_entries.*')->latest('processed_at');
    }

    public function productionOrders(int $companyId): Builder
    {
        $cut = DB::table('production_order_stage_entries')->selectRaw('production_order_id, SUM(good_quantity) as cut_qty')->where('stage', 'cutting')->groupBy('production_order_id');
        $sew = DB::table('production_order_stage_entries')->selectRaw('production_order_id, SUM(input_quantity) as sew_qty')->where('stage', 'sewing')->groupBy('production_order_id');
        return ProductionOrder::query()->where('production_orders.company_id', $companyId)->where('production_orders.status', 'in_progress')
            ->joinSub($cut, 'cut_stage', fn ($join) => $join->on('cut_stage.production_order_id', '=', 'production_orders.id'))
            ->leftJoinSub($sew, 'sew_stage', fn ($join) => $join->on('sew_stage.production_order_id', '=', 'production_orders.id'))
            ->whereRaw('COALESCE(cut_stage.cut_qty,0) > COALESCE(sew_stage.sew_qty,0)')
            ->with(['outputProduct:id,code,name,style_code,unit_id', 'outputProduct.unit:id,name,symbol', 'materials.product:id,code,name,unit_id'])
            ->select(['production_orders.id', 'production_orders.company_id', 'production_orders.number', 'production_orders.buyer_po_number', 'production_orders.output_product_id', 'production_orders.planned_quantity', 'production_orders.status'])
            ->selectRaw('COALESCE(cut_stage.cut_qty,0) - COALESCE(sew_stage.sew_qty,0) as available_cutting_quantity')
            ->latest('production_orders.created_at');
    }
}

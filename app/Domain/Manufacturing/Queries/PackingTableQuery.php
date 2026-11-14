<?php
namespace App\Domain\Manufacturing\Queries;

use App\Models\{ProductionOrder,ProductionOrderStageEntry};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PackingTableQuery
{
    public function builder(int $companyId,?string $from=null,?string $to=null):Builder
    {
        return ProductionOrderStageEntry::query()->where('stage','packing')->whereDate('processed_at','>=',$from?:'1000-01-01')->whereDate('processed_at','<=',$to?:'9999-12-31')
            ->whereHas('productionOrder',fn(Builder $q)=>$q->where('company_id',$companyId))
            ->with(['productionOrder:id,company_id,number,buyer_po_number,no_cbd,output_product_id','productionOrder.stageEntries'=>fn($q)=>$q->where('stage','sewing')->select('id','production_order_id','good_quantity'),'productionOrder.outputProduct:id,code,name,style_code,unit_id','productionOrder.outputProduct.unit:id,name,symbol','processor:id,name,username','materialProduct:id,code,name,unit_id','materialProduct.unit:id,name,symbol'])
            ->select('production_order_stage_entries.*')->latest('processed_at');
    }
    public function productionOrders(int $companyId):Builder
    {
        $sew=DB::table('production_order_stage_entries')->selectRaw('production_order_id,SUM(good_quantity) sewing_qty')->where('stage','sewing')->whereNull('deleted_at')->groupBy('production_order_id');
        $pack=DB::table('production_order_stage_entries')->selectRaw('production_order_id,SUM(input_quantity) packing_qty')->where('stage','packing')->whereNull('deleted_at')->groupBy('production_order_id');
        return ProductionOrder::query()->where('production_orders.company_id',$companyId)->where('production_orders.status','in_progress')
            ->joinSub($sew,'sew_stage',fn($j)=>$j->on('sew_stage.production_order_id','=','production_orders.id'))
            ->leftJoinSub($pack,'pack_stage',fn($j)=>$j->on('pack_stage.production_order_id','=','production_orders.id'))
            ->whereRaw('COALESCE(sew_stage.sewing_qty,0)>COALESCE(pack_stage.packing_qty,0)')
            ->with(['outputProduct:id,code,name,style_code,unit_id','outputProduct.unit:id,name,symbol','materials.product:id,code,name,unit_id'])
            ->select(['production_orders.id','production_orders.company_id','production_orders.number','production_orders.buyer_po_number','production_orders.no_cbd','production_orders.output_product_id','production_orders.planned_quantity','production_orders.status'])
            ->selectRaw('COALESCE(sew_stage.sewing_qty,0)-COALESCE(pack_stage.packing_qty,0) as available_sewing_quantity')->latest('production_orders.created_at');
    }
}

<?php
namespace App\Domain\Manufacturing\Queries;

use App\Models\{ProductionOrder,ProductionOrderStageEntry};
use Illuminate\Database\Eloquent\Builder;

class CuttingTableQuery
{
    public function builder(int $companyId,?string $from=null,?string $to=null): Builder
    {
        return ProductionOrderStageEntry::query()->where('stage','cutting')
            ->whereDate('processed_at','>=',$from ?: '1000-01-01')->whereDate('processed_at','<=',$to ?: '9999-12-31')
            ->whereHas('productionOrder',fn(Builder $q)=>$q->where('company_id',$companyId))
            ->with(['productionOrder:id,company_id,number,buyer_po_number,output_product_id','productionOrder.outputProduct:id,code,name,style_code,unit_id','productionOrder.outputProduct.unit:id,name,symbol','processor:id,name,username','materialProduct:id,code,name,unit_id','materialProduct.unit:id,name,symbol'])
            ->select('production_order_stage_entries.*')->latest('processed_at');
    }
    public function productionOrders(int $companyId): Builder
    {
        return ProductionOrder::query()->where('company_id',$companyId)->where('status','in_progress')
            ->with(['outputProduct:id,code,name,style_code,unit_id','outputProduct.unit:id,name,symbol','materials.product:id,code,name,unit_id'])
            ->select(['id','company_id','number','buyer_po_number','output_product_id','planned_quantity','status'])->latest('created_at');
    }
}

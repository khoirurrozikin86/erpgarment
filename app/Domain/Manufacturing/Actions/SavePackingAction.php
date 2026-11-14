<?php
namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Services\EnsureProductionMaterialsIssued;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{InventoryLot,Product,ProductStock,ProductionOrder,ProductionOrderStageEntry,StockMovement};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavePackingAction
{
    public function __construct(private CompanyContext $company, private DocumentNumberService $numbers, private AuditLogService $audit, private EnsureProductionMaterialsIssued $ensureMaterials) {}

    public function create(array $data, int $userId): ProductionOrderStageEntry
    {
        return DB::transaction(function () use ($data, $userId) {
            $order = ProductionOrder::query()->where('company_id',$this->company->id())->lockForUpdate()->findOrFail($data['production_order_id']);
            $this->assertOrder($order); $this->ensureMaterials->__invoke($order); $this->assertMaterial($order,(int)$data['material_product_id']);
            [$input,$good,$reject] = $this->quantities($data); $this->assertAvailable($order,$input);
            $entry = $order->stageEntries()->create(['stage'=>'packing','packing_number'=>$this->numbers->generate('packing'),'material_product_id'=>$data['material_product_id'],'material_waste_quantity'=>$data['material_waste_quantity'],'input_quantity'=>$input,'good_quantity'=>$good,'rejected_quantity'=>$reject,'waste_quantity'=>0,'processed_by'=>$userId,'processed_at'=>Carbon::parse($data['packing_date'])->startOfDay()]);
            if ($good > 0) $this->adjustFinishedGoods($entry,$order,$good,$userId);
            $this->audit->log('CREATE','PACKING',"Mencatat Packing {$entry->packing_number} untuk MO {$order->number}",$entry,null,$entry->toArray(),$userId);
            return $entry->load(['productionOrder.outputProduct.unit','materialProduct.unit','processor']);
        });
    }

    public function update(ProductionOrderStageEntry $entry,array $data,int $userId): ProductionOrderStageEntry
    {
        return DB::transaction(function () use ($entry,$data,$userId) {
            $row=ProductionOrderStageEntry::query()->where('stage','packing')->lockForUpdate()->findOrFail($entry->id);
            if ($row->productionOrder()->firstOrFail()->stageEntries()->where('stage','finishing')->exists()) throw ValidationException::withMessages(['packing'=>__('ui.packing.downstream_exists')]);
            if ((int)$row->production_order_id !== (int)$data['production_order_id']) throw ValidationException::withMessages(['production_order_id'=>__('ui.packing.mo_immutable')]);
            $order=ProductionOrder::query()->where('company_id',$this->company->id())->lockForUpdate()->findOrFail($row->production_order_id);
            $this->assertOrder($order); $this->ensureMaterials->__invoke($order); $this->assertMaterial($order,(int)$data['material_product_id']);
            [$input,$good,$reject]=$this->quantities($data); $this->assertAvailable($order,$input,$row->id);
            $old=$row->toArray(); $delta=round($good-(float)$row->good_quantity,4);
            $row->update(['material_product_id'=>$data['material_product_id'],'material_waste_quantity'=>$data['material_waste_quantity'],'input_quantity'=>$input,'good_quantity'=>$good,'rejected_quantity'=>$reject,'waste_quantity'=>0,'processed_at'=>Carbon::parse($data['packing_date'])->startOfDay()]);
            if(abs($delta)>0.00001)$this->adjustFinishedGoods($row,$order,$delta,$userId);
            $this->audit->log('UPDATE','PACKING',"Memperbarui Packing {$row->packing_number}",$row,$old,$row->fresh()->toArray(),$userId);
            return $row->fresh(['productionOrder.outputProduct.unit','materialProduct.unit','processor']);
        });
    }

    public function delete(ProductionOrderStageEntry $entry,int $userId): void
    {
        DB::transaction(function () use ($entry,$userId) {
            $row=ProductionOrderStageEntry::query()->where('stage','packing')->lockForUpdate()->findOrFail($entry->id);
            $order=ProductionOrder::query()->where('company_id',$this->company->id())->lockForUpdate()->findOrFail($row->production_order_id);
            if ($order->stageEntries()->where('stage','finishing')->exists()) throw ValidationException::withMessages(['packing'=>__('ui.packing.downstream_exists')]);
            $old=$row->toArray(); if((float)$row->good_quantity>0)$this->adjustFinishedGoods($row,$order,-(float)$row->good_quantity,$userId);
            $row->delete();
            $this->audit->log('DELETE','PACKING',"Menghapus Packing {$row->packing_number}",$row,$old,['deleted_at'=>now()->toDateTimeString()],$userId);
        });
    }

    private function quantities(array $data): array
    {
        $input=round((float)$data['input_quantity'],4);$good=round((float)$data['send_to_finished_goods'],4);$reject=round((float)$data['rejected_quantity'],4);
        if(abs($good+$reject-$input)>0.0001)throw ValidationException::withMessages(['send_to_finished_goods'=>__('ui.packing.output_must_match_input')]);
        return[$input,$good,$reject];
    }
    private function assertOrder(ProductionOrder $order):void{if($order->status!=='in_progress')throw ValidationException::withMessages(['production_order_id'=>__('ui.packing.mo_not_active')]);}
    private function assertMaterial(ProductionOrder $order,int $productId):void{if(!$order->materials()->where('product_id',$productId)->exists())throw ValidationException::withMessages(['material_product_id'=>__('ui.packing.material_not_in_mo')]);}
    private function assertAvailable(ProductionOrder $order,float $input,?int $exceptId=null):void
    {
        $sewing=(float)$order->stageEntries()->where('stage','sewing')->sum('good_quantity');
        $packed=(float)$order->stageEntries()->where('stage','packing')->when($exceptId,fn($q)=>$q->where('id','!=',$exceptId))->sum('input_quantity');
        if($input>$sewing-$packed+0.0001)throw ValidationException::withMessages(['input_quantity'=>__('ui.packing.qty_exceeds_sewing')]);
    }
    private function adjustFinishedGoods(ProductionOrderStageEntry $entry,ProductionOrder $order,float $delta,int $userId):void
    {
        $product=Product::query()->where('track_stock',true)->lockForUpdate()->find($order->output_product_id);
        if(!$product)throw ValidationException::withMessages(['stage'=>__('ui.workflow.finished_good_stock_tracking_required')]);
        $stock=ProductStock::query()->firstOrCreate(['product_id'=>$product->id],['quantity'=>0,'average_unit_cost'=>0]);
        $stock=ProductStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();$before=round((float)$stock->quantity,4);$after=round($before+$delta,4);
        if($after<0)throw ValidationException::withMessages(['stock'=>__('ui.packing.stock_correction_blocked')]);
        $stock->update(['quantity'=>$after]);
        $movement=StockMovement::query()->create(['product_id'=>$product->id,'production_order_stage_entry_id'=>$entry->id,'created_by'=>$userId,'movement_type'=>$delta<0?'production_output_reversal':'production_output','quantity'=>$delta,'quantity_before'=>$before,'quantity_after'=>$after,'cost_amount'=>0,'notes'=>__('ui.packing.stock_reference',['number'=>$entry->packing_number ?: $order->number])]);
        if($delta>0){$lot=InventoryLot::query()->create(['product_id'=>$product->id,'lot_number'=>'FG-'.$order->number.'-PKG-'.$entry->id.'-'.$movement->id,'received_at'=>now(),'initial_quantity'=>$delta,'current_quantity'=>$delta,'unit_cost'=>$product->purchase_price,'source_type'=>'production']);$movement->lotAllocations()->create(['inventory_lot_id'=>$lot->id,'quantity'=>$delta,'unit_cost'=>$lot->unit_cost]);}
    }
}

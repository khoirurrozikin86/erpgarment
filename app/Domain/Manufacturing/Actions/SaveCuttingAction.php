<?php
namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Services\EnsureProductionMaterialsIssued;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{ProductionOrder, ProductionOrderStageEntry};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveCuttingAction
{
    public function __construct(private CompanyContext $company, private DocumentNumberService $numbers, private AuditLogService $audit, private EnsureProductionMaterialsIssued $ensureMaterials) {}

    public function create(array $data, int $userId): ProductionOrderStageEntry
    {
        return DB::transaction(function() use($data,$userId){
            $order=ProductionOrder::query()->where('company_id',$this->company->id())->lockForUpdate()->findOrFail($data['production_order_id']);
            $this->assertOrder($order); $this->ensureMaterials->__invoke($order);
            $this->assertMaterialBelongs($order,(int)$data['material_product_id']);
            $input=round((float)$data['input_quantity'],4); $good=round((float)$data['send_to_sewing'],4); $reject=round((float)$data['rejected_quantity'],4);
            $this->assertQuantity($order,$input,$good,$reject);
            $entry=$order->stageEntries()->create(['stage'=>'cutting','cutting_number'=>$this->numbers->generate('cutting'),'material_product_id'=>$data['material_product_id'],'material_waste_quantity'=>$data['material_waste_quantity'],'input_quantity'=>$input,'good_quantity'=>$good,'rejected_quantity'=>$reject,'waste_quantity'=>0,'processed_by'=>$userId,'processed_at'=>Carbon::parse($data['cutting_date'])->startOfDay()]);
            $this->audit->log('CREATE','CUTTING',"Mencatat Cutting {$entry->cutting_number} untuk MO {$order->number}",$entry,null,$entry->toArray(),$userId);
            return $entry->load(['productionOrder.outputProduct','materialProduct']);
        });
    }

    public function update(ProductionOrderStageEntry $entry,array $data,int $userId): ProductionOrderStageEntry
    {
        return DB::transaction(function()use($entry,$data,$userId){
            $row=ProductionOrderStageEntry::query()->where('stage','cutting')->lockForUpdate()->findOrFail($entry->id);
            $order=ProductionOrder::query()->where('company_id',$this->company->id())->lockForUpdate()->findOrFail($row->production_order_id);
            $this->assertOrder($order); $this->ensureMaterials->__invoke($order); $this->assertNoDownstream($order);
            $this->assertMaterialBelongs($order,(int)$data['material_product_id']);
            $input=round((float)$data['input_quantity'],4);$good=round((float)$data['send_to_sewing'],4);$reject=round((float)$data['rejected_quantity'],4);
            $this->assertQuantity($order,$input,$good,$reject,$row->id);
            $old=$row->toArray();
            $row->update(['material_product_id'=>$data['material_product_id'],'material_waste_quantity'=>$data['material_waste_quantity'],'input_quantity'=>$input,'good_quantity'=>$good,'rejected_quantity'=>$reject,'waste_quantity'=>0,'processed_at'=>Carbon::parse($data['cutting_date'])->startOfDay()]);
            $this->audit->log('UPDATE','CUTTING',"Memperbarui Cutting {$row->cutting_number}",$row,$old,$row->fresh()->toArray(),$userId);
            return $row->fresh(['productionOrder.outputProduct','materialProduct']);
        });
    }

    public function delete(ProductionOrderStageEntry $entry,int $userId): void
    {
        DB::transaction(function()use($entry,$userId){
            $row=ProductionOrderStageEntry::query()->where('stage','cutting')->lockForUpdate()->findOrFail($entry->id);
            $order=ProductionOrder::query()->where('company_id',$this->company->id())->lockForUpdate()->findOrFail($row->production_order_id);$this->assertNoDownstream($order);
            $old=$row->toArray();$row->delete();
            $this->audit->log('DELETE','CUTTING',"Menghapus Cutting {$row->cutting_number}",$row,$old,null,$userId);
        });
    }

    private function assertOrder(ProductionOrder $order): void
    {
        if($order->status!=='in_progress') throw ValidationException::withMessages(['production_order_id'=>__('ui.cutting.mo_not_active')]);
    }
    private function assertMaterialBelongs(ProductionOrder $order,int $productId): void
    {
        if(!$order->materials()->where('product_id',$productId)->exists()) throw ValidationException::withMessages(['material_product_id'=>__('ui.cutting.material_not_in_mo')]);
    }
    private function assertQuantity(ProductionOrder $order,float $input,float $good,float $reject,?int $exceptId=null): void
    {
        if(abs($good+$reject-$input)>0.0001) throw ValidationException::withMessages(['send_to_sewing'=>__('ui.cutting.output_must_match_input')]);
        $used=(float)$order->stageEntries()->where('stage','cutting')->when($exceptId,fn($q)=>$q->where('id','!=',$exceptId))->sum('input_quantity');
        if($input>(float)$order->planned_quantity-$used+0.0001) throw ValidationException::withMessages(['input_quantity'=>__('ui.cutting.qty_exceeds_mo')]);
    }
    private function assertNoDownstream(ProductionOrder $order): void
    {
        if($order->stageEntries()->whereIn('stage',['sewing','finishing','packing'])->exists()) throw ValidationException::withMessages(['cutting'=>__('ui.cutting.downstream_exists')]);
    }
}

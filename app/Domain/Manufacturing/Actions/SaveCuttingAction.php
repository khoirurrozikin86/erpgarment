<?php
namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Services\EnsureProductionMaterialsIssued;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{ProductionOrder, ProductionOrderMaterial, ProductionOrderStageEntry};
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
            $this->assertOrder($order);
            $materialId = isset($data['material_product_id']) ? (int) $data['material_product_id'] : null;
            $this->ensureMaterials->forFabric($order, $materialId);
            if ($order->status === 'released') {
                $order->update(['status' => 'in_progress']);
            }
            if ($materialId !== null) $this->assertMaterialBelongs($order, $materialId);
            $input=round((float) ($data['input_quantity'] ?? 0),4); $good=round((float)$data['send_to_sewing'],4); $reject=round((float)$data['rejected_quantity'],4);
            $this->assertQuantity($order,$input,$good,$reject,null,$materialId);
            $entry=$order->stageEntries()->create(['stage'=>'cutting','cutting_number'=>$this->numbers->generate('cutting'),'material_product_id'=>$materialId,'material_waste_quantity'=>$data['material_waste_quantity'] ?? 0,'input_quantity'=>$input,'good_quantity'=>$good,'rejected_quantity'=>$reject,'waste_quantity'=>0,'processed_by'=>$userId,'processed_at'=>Carbon::parse($data['cutting_date'])->startOfDay()]);
            $this->audit->log('CREATE','CUTTING',"Mencatat Cutting {$entry->cutting_number} untuk MO {$order->number}",$entry,null,$entry->toArray(),$userId);
            return $entry->load(['productionOrder.outputProduct','materialProduct']);
        });
    }

    public function createBatch(array $data, int $userId): void
    {
        DB::transaction(function () use ($data, $userId) {
            $selected = ProductionOrder::where('company_id', $this->company->id())->findOrFail($data['production_order_id']);
            $orders = ProductionOrder::where('company_id', $this->company->id())
                ->when($selected->production_order_header_id,
                    fn ($q) => $q->where('production_order_header_id', $selected->production_order_header_id),
                    fn ($q) => $q->where('number', $selected->number))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $saved = 0;
            foreach ($data['lines'] as $line) {
                if (!$orders->has($line['production_order_id'])) throw ValidationException::withMessages(['lines' => __('variants.cutting_wrong_order')]);
                if ((float) ($line['input_quantity'] ?? 0) == 0 && (float)$line['send_to_sewing'] == 0 && (float)$line['rejected_quantity'] == 0 && (float) ($line['material_waste_quantity'] ?? 0) == 0) continue;
                $this->create([...$line, 'cutting_date' => $data['cutting_date']], $userId);
                $saved++;
            }
            if (!$saved) throw ValidationException::withMessages(['lines' => __('variants.cutting_empty')]);
        });
    }

    public function update(ProductionOrderStageEntry $entry,array $data,int $userId): ProductionOrderStageEntry
    {
        return DB::transaction(function()use($entry,$data,$userId){
            $row=ProductionOrderStageEntry::query()->where('stage','cutting')->lockForUpdate()->findOrFail($entry->id);
            $order=ProductionOrder::query()->where('company_id',$this->company->id())->lockForUpdate()->findOrFail($row->production_order_id);
            if ((int)$order->id !== (int)$data['production_order_id']) throw ValidationException::withMessages(['production_order_id' => __('variants.cutting_wrong_order')]);
            $this->assertOrder($order);
            $data += $row->only(['material_product_id', 'input_quantity', 'material_waste_quantity']);
            $materialId = isset($data['material_product_id']) ? (int) $data['material_product_id'] : null;
            $this->ensureMaterials->forFabric($order, $materialId);
            $this->assertNoDownstream($order);
            if ($materialId !== null) $this->assertMaterialBelongs($order, $materialId);
            $input=round((float) ($data['input_quantity'] ?? 0),4);$good=round((float)$data['send_to_sewing'],4);$reject=round((float)$data['rejected_quantity'],4);
            $this->assertQuantity($order,$input,$good,$reject,$row->id,$materialId);
            $old=$row->toArray();
            $row->update(['material_product_id'=>$materialId,'material_waste_quantity'=>$data['material_waste_quantity'] ?? 0,'input_quantity'=>$input,'good_quantity'=>$good,'rejected_quantity'=>$reject,'waste_quantity'=>0,'processed_at'=>Carbon::parse($data['cutting_date'])->startOfDay()]);
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
        if(!in_array($order->status,['released','in_progress'],true)) throw ValidationException::withMessages(['production_order_id'=>__('ui.cutting.mo_not_active')]);
        if (!$order->materials()->exists()) {
            throw ValidationException::withMessages(['production_order_id' => __('variants.mo_material_request_required')]);
        }
    }
    private function assertMaterialBelongs(ProductionOrder $order,int $productId): void
    {
        if(!ProductionOrderMaterial::query()
            ->where('production_order_id',$order->id)
            ->where('product_id',$productId)
            ->where(fn ($query) => $query
                ->whereHas('billOfMaterial', fn ($bom) => $bom->where('bom_type','fabric'))
                ->orWhereHas('product', fn ($product) => $product->where('garment_item_type','fabric')))
            ->exists()) throw ValidationException::withMessages(['material_product_id'=>__('ui.cutting.material_not_in_mo')]);
    }
    private function assertQuantity(ProductionOrder $order,float $input,float $good,float $reject,?int $exceptId=null,?int $fabricProductId=null): void
    {
        if($good < 0 || $reject < 0) {
            throw ValidationException::withMessages(['send_to_sewing' => __('ui.cutting.quantity_invalid')]);
        }
        $remaining = app(\App\Domain\Manufacturing\Services\ProductionStageBalance::class)->remaining($order, 'cutting', $exceptId);
        if ($good + $reject > $remaining + 0.0001) throw ValidationException::withMessages(['send_to_sewing' => __('variants.cutting_exceeds_detail')]);
        if ($fabricProductId === null) {
            if ($input > 0 || $good + $reject <= 0) {
                throw ValidationException::withMessages(['send_to_sewing' => __('ui.cutting.quantity_invalid')]);
            }
            return;
        }
        if ($input <= 0) {
            throw ValidationException::withMessages(['input_quantity' => __('ui.cutting.quantity_invalid')]);
        }
        $used = (float) $order->stageEntries()->where('stage', 'cutting')
            ->where('material_product_id', $fabricProductId)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))->sum('input_quantity');
        $totalQuantity=(float)$order->materials()
            ->where('product_id',$fabricProductId)
            ->sum('required_quantity');
        if($input>$totalQuantity-$used+0.0001) throw ValidationException::withMessages(['input_quantity'=>__('ui.cutting.qty_exceeds_mo')]);
    }
    private function assertNoDownstream(ProductionOrder $order): void
    {
        if($order->stageEntries()->whereIn('stage',['sewing','finishing','packing'])->exists()) throw ValidationException::withMessages(['cutting'=>__('ui.cutting.downstream_exists')]);
    }
}

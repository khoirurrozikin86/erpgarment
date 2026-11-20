<?php
namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Services\EnsureProductionMaterialsIssued;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{ProductionOrder,ProductionOrderStageEntry};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveSewingAction
{
    public function __construct(private CompanyContext $company, private DocumentNumberService $numbers, private AuditLogService $audit, private EnsureProductionMaterialsIssued $ensureMaterials) {}

    public function create(array $data, int $userId): ProductionOrderStageEntry
    {
        return DB::transaction(function () use ($data, $userId) {
            $order = ProductionOrder::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($data['production_order_id']);
            $this->assertOrder($order); $this->ensureMaterials->__invoke($order); if (isset($data['material_product_id'])) $this->assertMaterial($order, (int) $data['material_product_id']);
            [$input, $good, $reject] = $this->quantities($data); $this->assertAvailable($order, $input);
            $entry = $order->stageEntries()->create([
                'stage' => 'sewing', 'sewing_number' => $this->numbers->generate('sewing'),
                'material_product_id' => $data['material_product_id'] ?? null, 'material_waste_quantity' => $data['material_waste_quantity'] ?? 0,
                'input_quantity' => $input, 'good_quantity' => $good, 'rejected_quantity' => $reject, 'waste_quantity' => 0,
                'processed_by' => $userId, 'processed_at' => Carbon::parse($data['sewing_date'])->startOfDay(),
            ]);
            $this->audit->log('CREATE', 'SEWING', "Mencatat Sewing {$entry->sewing_number} untuk MO {$order->number}", $entry, null, $entry->toArray(), $userId);
            return $entry->load(['productionOrder.outputProduct.unit', 'materialProduct.unit', 'processor']);
        });
    }

    public function createBatch(array $data, int $userId): void
    {
        DB::transaction(function () use ($data, $userId) {
            $selected=ProductionOrder::where('company_id',$this->company->id())->findOrFail($data['production_order_id']);
            $orders=ProductionOrder::where('company_id',$this->company->id())
                ->when($selected->production_order_header_id,
                    fn ($q) => $q->where('production_order_header_id',$selected->production_order_header_id),
                    fn ($q) => $q->where('number',$selected->number))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $saved=0;
            foreach ($data['lines'] as $index=>$line) {
                if (!$orders->has($line['production_order_id'])) throw ValidationException::withMessages(['lines'=>__('variants.cutting_wrong_order')]);
                if ((float)$line['input_quantity']==0 && (float)$line['send_to_packing']==0 && (float)$line['rejected_quantity']==0 && (float)($line['material_waste_quantity'] ?? 0)==0) continue;
                if ((float)$line['input_quantity']<=0) throw ValidationException::withMessages(['lines.'.$index=>__('variants.sewing_line_required')]);
                $this->create([...$line,'sewing_date'=>$data['sewing_date']],$userId);
                $saved++;
            }
            if (!$saved) throw ValidationException::withMessages(['lines'=>__('variants.sewing_line_required')]);
        });
    }

    public function update(ProductionOrderStageEntry $entry, array $data, int $userId): ProductionOrderStageEntry
    {
        return DB::transaction(function () use ($entry, $data, $userId) {
            $row = ProductionOrderStageEntry::query()->where('stage', 'sewing')->lockForUpdate()->findOrFail($entry->id);
            if ((int) $row->production_order_id !== (int) $data['production_order_id']) throw ValidationException::withMessages(['production_order_id' => __('ui.sewing.mo_immutable')]);
            $order = ProductionOrder::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($row->production_order_id);
            $this->assertNoDownstream($order);
            $data += $row->only(['material_product_id', 'material_waste_quantity']);
            $this->assertOrder($order); $this->ensureMaterials->__invoke($order); if (isset($data['material_product_id'])) $this->assertMaterial($order, (int) $data['material_product_id']);
            [$input, $good, $reject] = $this->quantities($data); $this->assertAvailable($order, $input, $row->id);
            $old = $row->toArray();
            $row->update(['material_product_id' => $data['material_product_id'] ?? null, 'material_waste_quantity' => $data['material_waste_quantity'] ?? 0, 'input_quantity' => $input, 'good_quantity' => $good, 'rejected_quantity' => $reject, 'waste_quantity' => 0, 'processed_at' => Carbon::parse($data['sewing_date'])->startOfDay()]);
            $this->audit->log('UPDATE', 'SEWING', "Memperbarui Sewing {$row->sewing_number}", $row, $old, $row->fresh()->toArray(), $userId);
            return $row->fresh(['productionOrder.outputProduct.unit', 'materialProduct.unit', 'processor']);
        });
    }

    public function delete(ProductionOrderStageEntry $entry, int $userId): void
    {
        DB::transaction(function () use ($entry, $userId) {
            $row = ProductionOrderStageEntry::query()->where('stage', 'sewing')->lockForUpdate()->findOrFail($entry->id);
            $order = ProductionOrder::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($row->production_order_id);
            if ($order->stageEntries()->whereIn('stage', ['finishing', 'packing'])->exists()) throw ValidationException::withMessages(['sewing' => __('ui.sewing.downstream_exists')]);
            $old = $row->toArray(); $row->delete();
            $this->audit->log('DELETE', 'SEWING', "Menghapus Sewing {$row->sewing_number}", $row, $old, null, $userId);
        });
    }

    private function quantities(array $data): array
    {
        $input = round((float) $data['input_quantity'], 4); $good = round((float) $data['send_to_packing'], 4); $reject = round((float) $data['rejected_quantity'], 4);
        if (abs($good + $reject - $input) > 0.0001) throw ValidationException::withMessages(['send_to_packing' => __('ui.sewing.output_must_match_input')]);
        return [$input, $good, $reject];
    }
    private function assertOrder(ProductionOrder $order): void { if ($order->status !== 'in_progress') throw ValidationException::withMessages(['production_order_id' => __('ui.sewing.mo_not_active')]); }
    private function assertMaterial(ProductionOrder $order, int $productId): void { if (!$order->materials()->where('product_id', $productId)->exists()) throw ValidationException::withMessages(['material_product_id' => __('ui.sewing.material_not_in_mo')]); }
    private function assertAvailable(ProductionOrder $order, float $input, ?int $exceptId = null): void
    {
        $available = app(\App\Domain\Manufacturing\Services\ProductionStageBalance::class)->remaining($order, 'sewing', $exceptId);
        if ($input > $available + 0.0001) throw ValidationException::withMessages(['input_quantity' => __('ui.sewing.qty_exceeds_cutting')]);
    }
    private function assertNoDownstream(ProductionOrder $order): void { if ($order->stageEntries()->whereIn('stage', ['finishing','packing'])->exists()) throw ValidationException::withMessages(['sewing' => __('ui.sewing.downstream_exists')]); }
}

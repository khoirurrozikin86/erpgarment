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
            $this->assertOrder($order); $this->ensureMaterials->__invoke($order); $this->assertMaterial($order, (int) $data['material_product_id']);
            [$input, $good, $reject] = $this->quantities($data); $this->assertAvailable($order, $input);
            $entry = $order->stageEntries()->create([
                'stage' => 'sewing', 'sewing_number' => $this->numbers->generate('sewing'),
                'material_product_id' => $data['material_product_id'], 'material_waste_quantity' => $data['material_waste_quantity'],
                'input_quantity' => $input, 'good_quantity' => $good, 'rejected_quantity' => $reject, 'waste_quantity' => 0,
                'processed_by' => $userId, 'processed_at' => Carbon::parse($data['sewing_date'])->startOfDay(),
            ]);
            $this->audit->log('CREATE', 'SEWING', "Mencatat Sewing {$entry->sewing_number} untuk MO {$order->number}", $entry, null, $entry->toArray(), $userId);
            return $entry->load(['productionOrder.outputProduct.unit', 'materialProduct.unit', 'processor']);
        });
    }

    public function update(ProductionOrderStageEntry $entry, array $data, int $userId): ProductionOrderStageEntry
    {
        return DB::transaction(function () use ($entry, $data, $userId) {
            $row = ProductionOrderStageEntry::query()->where('stage', 'sewing')->lockForUpdate()->findOrFail($entry->id);
            if ((int) $row->production_order_id !== (int) $data['production_order_id']) throw ValidationException::withMessages(['production_order_id' => __('ui.sewing.mo_immutable')]);
            $order = ProductionOrder::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($row->production_order_id);
            $this->assertNoDownstream($order);
            $this->assertOrder($order); $this->ensureMaterials->__invoke($order); $this->assertMaterial($order, (int) $data['material_product_id']);
            [$input, $good, $reject] = $this->quantities($data); $this->assertAvailable($order, $input, $row->id);
            $old = $row->toArray();
            $row->update(['material_product_id' => $data['material_product_id'], 'material_waste_quantity' => $data['material_waste_quantity'], 'input_quantity' => $input, 'good_quantity' => $good, 'rejected_quantity' => $reject, 'waste_quantity' => 0, 'processed_at' => Carbon::parse($data['sewing_date'])->startOfDay()]);
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
        $cut = (float) $order->stageEntries()->where('stage', 'cutting')->sum('good_quantity');
        $used = (float) $order->stageEntries()->where('stage', 'sewing')->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->sum('input_quantity');
        if ($input > $cut - $used + 0.0001) throw ValidationException::withMessages(['input_quantity' => __('ui.sewing.qty_exceeds_cutting')]);
    }
    private function assertNoDownstream(ProductionOrder $order): void { if ($order->stageEntries()->whereIn('stage', ['finishing','packing'])->exists()) throw ValidationException::withMessages(['sewing' => __('ui.sewing.downstream_exists')]); }
}

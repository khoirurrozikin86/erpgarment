<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{ProductionOrder, ProductionOrderStageEntry};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveFinishedGoodsAction
{
    public function __construct(private CompanyContext $company, private DocumentNumberService $numbers, private AuditLogService $audit) {}

    public function create(array $data, int $userId): ProductionOrderStageEntry
    {
        return DB::transaction(function () use ($data, $userId) {
            $order = ProductionOrder::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($data['production_order_id']);
            $this->assertActive($order);
            [$received, $good] = $this->quantities($data);
            $this->assertAvailable($order, $received);
            $entry = $order->stageEntries()->create([
                'stage' => 'finishing', 'finished_goods_number' => $this->numbers->generate('finished_goods'),
                'input_quantity' => $received, 'good_quantity' => $good, 'rejected_quantity' => round($received - $good, 4),
                'waste_quantity' => 0, 'processed_by' => $userId, 'processed_at' => Carbon::parse($data['finished_goods_date'])->startOfDay(),
            ]);
            $this->audit->log('CREATE', 'FINISHED_GOODS', "Mencatat barang jadi {$entry->finished_goods_number} dari Packing MO {$order->number}", $entry, null, $entry->toArray(), $userId);
            return $entry;
        });
    }

    public function update(ProductionOrderStageEntry $entry, array $data, int $userId): ProductionOrderStageEntry
    {
        return DB::transaction(function () use ($entry, $data, $userId) {
            $row = ProductionOrderStageEntry::query()->where('stage', 'finishing')->lockForUpdate()->findOrFail($entry->id);
            if ((int) $row->production_order_id !== (int) $data['production_order_id']) throw ValidationException::withMessages(['production_order_id' => __('ui.finished_goods.mo_immutable')]);
            $order = ProductionOrder::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($row->production_order_id);
            $this->assertActive($order);
            [$received, $good] = $this->quantities($data);
            $this->assertAvailable($order, $received, $row->id);
            $old = $row->toArray();
            $row->update(['input_quantity' => $received, 'good_quantity' => $good, 'rejected_quantity' => round($received - $good, 4), 'processed_at' => Carbon::parse($data['finished_goods_date'])->startOfDay()]);
            $this->audit->log('UPDATE', 'FINISHED_GOODS', "Memperbarui barang jadi {$row->finished_goods_number}", $row, $old, $row->fresh()->toArray(), $userId);
            return $row->fresh();
        });
    }

    public function delete(ProductionOrderStageEntry $entry, int $userId): void
    {
        DB::transaction(function () use ($entry, $userId) {
            $row = ProductionOrderStageEntry::query()->where('stage', 'finishing')->lockForUpdate()->findOrFail($entry->id);
            $old = $row->toArray();
            $row->delete();
            $this->audit->log('DELETE', 'FINISHED_GOODS', "Menghapus barang jadi {$row->finished_goods_number}", $row, $old, ['deleted_at' => now()->toDateTimeString()], $userId);
        });
    }

    private function assertActive(ProductionOrder $order): void
    {
        if ($order->status !== 'in_progress') throw ValidationException::withMessages(['production_order_id' => __('ui.finished_goods.mo_not_active')]);
    }

    private function quantities(array $data): array
    {
        $received = round((float) $data['receive_from_packing'], 4);
        $good = round((float) $data['finished_good_quantity'], 4);
        if ($good > $received + 0.0001) throw ValidationException::withMessages(['finished_good_quantity' => __('ui.finished_goods.output_exceeds_receipt')]);
        return [$received, $good];
    }

    private function assertAvailable(ProductionOrder $order, float $received, ?int $exceptId = null): void
    {
        $packed = (float) $order->stageEntries()->where('stage', 'packing')->sum('good_quantity');
        $receivedBefore = (float) $order->stageEntries()->where('stage', 'finishing')->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->sum('input_quantity');
        if ($received > $packed - $receivedBefore + 0.0001) throw ValidationException::withMessages(['receive_from_packing' => __('ui.finished_goods.qty_exceeds_packing')]);
    }
}

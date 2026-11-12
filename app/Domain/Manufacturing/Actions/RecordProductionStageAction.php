<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Manufacturing\Services\EnsureProductionMaterialsIssued;
use App\Models\ProductStock;
use App\Models\InventoryLot;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderStageEntry;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordProductionStageAction
{
    private const STAGES = ['cutting', 'sewing', 'finishing', 'packing'];

    public function __construct(private AuditLogService $auditLog, private EnsureProductionMaterialsIssued $ensureMaterials) {}

    public function __invoke(ProductionOrder $productionOrder, array $data, int $userId): ProductionOrderStageEntry
    {
        return DB::transaction(function () use ($productionOrder, $data, $userId) {
            $order = ProductionOrder::query()->lockForUpdate()->findOrFail($productionOrder->id);
            if ($order->status !== 'in_progress') {
                throw ValidationException::withMessages(['status' => __('ui.workflow.stage_requires_active_order')]);
            }
            $this->ensureMaterials->__invoke($order);

            $stage = $data['stage'];
            $stageIndex = array_search($stage, self::STAGES, true);
            if ($stageIndex === false) {
                throw ValidationException::withMessages(['stage' => __('ui.workflow.stage_invalid')]);
            }

            $inputDone = (float) ProductionOrderStageEntry::query()
                ->where('production_order_id', $order->id)->where('stage', $stage)->sum('input_quantity');
            if ($stageIndex === 0) {
                $available = max(0, (float) $order->planned_quantity - $inputDone);
            } else {
                $previousGood = (float) ProductionOrderStageEntry::query()
                    ->where('production_order_id', $order->id)->where('stage', self::STAGES[$stageIndex - 1])->sum('good_quantity');
                $available = max(0, $previousGood - $inputDone);
            }

            $input = round((float) $data['input_quantity'], 4);
            if ($input - $available > 0.0000001) {
                throw ValidationException::withMessages([
                    'input_quantity' => __('ui.workflow.stage_input_exceeds_available', ['available' => number_format($available, 4, ',', '.')]),
                ]);
            }

            $good = round((float) ($data['good_quantity'] ?? 0), 4);
            $rejected = round((float) ($data['rejected_quantity'] ?? 0), 4);
            $waste = round((float) ($data['waste_quantity'] ?? 0), 4);
            if (abs(($good + $rejected + $waste) - $input) > 0.0001) {
                throw ValidationException::withMessages(['good_quantity' => __('ui.workflow.stage_output_must_match_input')]);
            }

            $entry = ProductionOrderStageEntry::create([
                'production_order_id' => $order->id,
                'stage' => $stage,
                'input_quantity' => $input,
                'good_quantity' => $good,
                'rejected_quantity' => $rejected,
                'waste_quantity' => $waste,
                'notes' => $data['notes'] ?? null,
                'processed_by' => $userId,
                'processed_at' => now(),
            ]);

            if ($stage === 'packing' && $good > 0) {
                $product = $order->outputProduct()->lockForUpdate()->firstOrFail();
                if (!$product->track_stock) {
                    throw ValidationException::withMessages(['stage' => __('ui.workflow.finished_good_stock_tracking_required')]);
                }
                $stock = ProductStock::query()->firstOrCreate(
                    ['product_id' => $product->id],
                    ['quantity' => 0, 'average_unit_cost' => 0],
                );
                $stock = ProductStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
                $before = round((float) $stock->quantity, 4);
                $after = round($before + $good, 4);
                $stock->update(['quantity' => $after]);
                $lot = InventoryLot::create([
                    'product_id' => $product->id,
                    'lot_number' => 'FG-' . $order->number . '-' . $entry->id,
                    'received_at' => now(),
                    'initial_quantity' => $good,
                    'current_quantity' => $good,
                    'unit_cost' => $product->purchase_price,
                    'source_type' => 'production',
                ]);
                $movement = StockMovement::create([
                    'product_id' => $product->id,
                    'production_order_stage_entry_id' => $entry->id,
                    'created_by' => $userId,
                    'movement_type' => 'production_output',
                    'quantity' => $good,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'cost_amount' => 0,
                    'notes' => __('ui.workflow.production_output_reference', ['number' => $order->number]),
                ]);
                $movement->lotAllocations()->create([
                    'inventory_lot_id' => $lot->id,
                    'quantity' => $good,
                    'unit_cost' => $lot->unit_cost,
                ]);
            }

            $totals = ProductionOrderStageEntry::query()->where('production_order_id', $order->id)
                ->selectRaw('stage, SUM(input_quantity) as input_quantity, SUM(good_quantity) as good_quantity')
                ->groupBy('stage')->get()->keyBy('stage');
            $allStagesComplete = true;
            foreach (self::STAGES as $index => $stageName) {
                $expected = $index === 0
                    ? (float) $order->planned_quantity
                    : (float) ($totals[self::STAGES[$index - 1]]->good_quantity ?? 0);
                $processed = (float) ($totals[$stageName]->input_quantity ?? 0);
                if (abs($expected - $processed) > 0.0001) {
                    $allStagesComplete = false;
                    break;
                }
            }
            if ($allStagesComplete) {
                $order->update(['status' => 'completed']);
            }

            $this->auditLog->log(
                action: 'STAGE_POST', module: 'PRODUCTION_ORDER',
                description: __('ui.workflow.production_stage_recorded_audit', ['stage' => __('ui.workflow.stage_' . $stage), 'number' => $order->number]),
                model: $order,
                oldValues: null,
                newValues: $entry->toArray(),
            );

            return $entry;
        });
    }
}

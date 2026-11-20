<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Purchasing\DTOs\MaterialRequestData;
use App\Domain\Purchasing\Services\MaterialRequestLineAggregator;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\MaterialRequest;
use Illuminate\Support\Facades\DB;

class CreateMaterialRequestAction
{
    public function __construct(
        private DocumentNumberService $numbers,
        private AuditLogService $auditLog,
    ) {}

    public function __invoke(MaterialRequestData $data, int $userId): MaterialRequest
    {
        return DB::transaction(function () use ($data, $userId) {
            if ($data->productionOrderId && $data->billOfMaterialId) {
                \App\Models\Company::whereKey(\App\Models\ProductionOrder::findOrFail($data->productionOrderId)->company_id)->lockForUpdate()->firstOrFail();
                $order = \App\Models\ProductionOrder::lockForUpdate()->findOrFail($data->productionOrderId);
                $result = app(\App\Domain\Purchasing\Services\MaterialOrderRequirements::class)
                    ->resolve($order, $order->company_id, $data->billOfMaterialId, true);
                $data->sourceDetails = $result['source_details'];
                $expected = collect($result['items'])->keyBy('product_id')->map(fn ($line) => round((float) $line['quantity'], 4))->all();
                $submitted = collect($data->items)->groupBy('product_id')->map(fn ($lines) => round($lines->sum('quantity'), 4))->all();
                ksort($expected); ksort($submitted);
                if ($expected != $submitted) throw \Illuminate\Validation\ValidationException::withMessages(['items' => __('variants.mo_material_totals')]);
            }
            $lines = app(MaterialRequestLineAggregator::class)->forStorage($data->items);
            if (collect($lines)->contains(fn ($line) => $line['quantity'] > 999999999999.9999)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['items' => __('ui.workflow.quantity_input_invalid')]);
            }
            $request = MaterialRequest::create([
                ...$data->toArray(),
                'number' => $this->numbers->generate('material_request'),
                'requested_by' => $userId,
                'status' => 'pending',
            ]);
            $request->items()->createMany($lines);

            $this->auditLog->log(
                action: 'CREATE', module: 'MATERIAL_REQUEST',
                description: "Mengajukan Material Request {$request->number}",
                model: $request, oldValues: null, newValues: $request->load('items')->toArray(),
            );

            return $request->load(['requester', 'items.product.unit']);
        });
    }
}

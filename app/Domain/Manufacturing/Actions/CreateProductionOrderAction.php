<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\BillOfMaterial;
use App\Models\ProductionOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateProductionOrderAction
{
    public function __construct(
        private CompanyContext $company,
        private AuditLogService $auditLog,
    ) {}

    public function __invoke(array $data, int $userId): ProductionOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $sharedHeader = [
                'buyer_po_number' => $data['buyer_po_number'] ?? null,
                'no_cbd' => $data['no_cbd'] ?? null,
                'currency' => $data['currency'],
                'destination' => $data['destination'] ?? null,
                'po_date' => $data['po_date'],
                'due_date' => $data['due_date'],
                'customer_id' => $data['customer_id'],
            ];
            $existingLine = ProductionOrder::query()->where('company_id', $this->company->id())
                ->where('number', $data['number'])->lockForUpdate()->first();
            $existingHeader = $existingLine ? [
                'buyer_po_number' => $existingLine->buyer_po_number,
                'no_cbd' => $existingLine->no_cbd,
                'currency' => $existingLine->currency,
                'destination' => $existingLine->destination,
                'po_date' => $existingLine->po_date?->format('Y-m-d'),
                'due_date' => $existingLine->due_date?->format('Y-m-d'),
                'customer_id' => $existingLine->customer_id,
            ] : null;
            if ($existingHeader && collect($sharedHeader)->contains(fn ($value, $key) => (string) ($existingHeader[$key] ?? '') !== (string) ($value ?? ''))) {
                throw ValidationException::withMessages(['number' => __('ui.workflow.material_order_header_mismatch')]);
            }

            $startDate = $data['po_date'] ?? now()->toDateString();
            $bom = BillOfMaterial::query()->where('company_id', $this->company->id())
                ->where('product_id', $data['output_product_id'])->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $startDate))
                ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $startDate))
                ->with('items')->lockForUpdate()->first();

            if (!$bom || (float) $bom->output_quantity <= 0 || $bom->items->isEmpty()) {
                throw ValidationException::withMessages(['output_product_id' => __('ui.workflow.active_bom_not_found')]);
            }

            $order = ProductionOrder::create([
                'company_id' => $this->company->id(),
                'number' => $data['number'],
                ...$sharedHeader,
                'price_per_unit' => $data['price_per_unit'] ?? 0,
                'total_price' => round((float) $data['planned_quantity'] * (float) ($data['price_per_unit'] ?? 0), 4),
                'description' => $data['description'] ?? null,
                'bill_of_material_id' => $bom->id,
                'output_product_id' => $bom->product_id,
                'bom_version' => $bom->version,
                'planned_quantity' => $data['planned_quantity'],
                'planned_start_date' => $data['po_date'],
                'planned_end_date' => $data['due_date'],
                'status' => 'released',
                'created_by' => $userId,
                'released_by' => $userId,
                'released_at' => now(),
            ]);

            $ratio = (float) $data['planned_quantity'] / (float) $bom->output_quantity;
            $order->materials()->createMany($bom->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'operation' => $item->operation,
                'quantity_per_output' => $item->quantity,
                'wastage_percent' => $item->wastage_percent,
                'required_quantity' => round((float) $item->quantity * $ratio * (1 + (float) $item->wastage_percent / 100), 4),
            ])->all());

            $this->auditLog->log(
                action: 'CREATE', module: 'MATERIAL_ORDER',
                description: __('ui.workflow.material_order_created_audit', ['number' => $order->number]),
                model: $order, oldValues: null,
                newValues: $order->load('materials.product')->toArray(),
            );

            return $order->load(['outputProduct.unit', 'materials.product.unit', 'creator']);
        });
    }
}

<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\BillOfMaterial;
use App\Models\ProductionOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateProductionOrderAction
{
    public function __construct(
        private CompanyContext $company,
        private DocumentNumberService $numbers,
        private AuditLogService $auditLog,
    ) {}

    public function __invoke(array $data, int $userId): ProductionOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $startDate = $data['planned_start_date'] ?? now()->toDateString();
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
                'number' => $this->numbers->generate('production_order'),
                'bill_of_material_id' => $bom->id,
                'output_product_id' => $bom->product_id,
                'bom_version' => $bom->version,
                'planned_quantity' => $data['planned_quantity'],
                'planned_start_date' => $data['planned_start_date'] ?? null,
                'planned_end_date' => $data['planned_end_date'] ?? null,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
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
                action: 'CREATE', module: 'PRODUCTION_ORDER',
                description: __('ui.workflow.production_order_created_audit', ['number' => $order->number]),
                model: $order, oldValues: null,
                newValues: $order->load('materials.product')->toArray(),
            );

            return $order->load(['outputProduct.unit', 'materials.product.unit', 'creator']);
        });
    }
}

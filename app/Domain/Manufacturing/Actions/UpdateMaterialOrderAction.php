<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\BillOfMaterial;
use App\Models\ProductionOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateMaterialOrderAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $auditLog) {}

    public function __invoke(ProductionOrder $order, array $data, int $userId): ProductionOrder
    {
        return DB::transaction(function () use ($order, $data, $userId) {
            $order = ProductionOrder::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($order->id);
            if ($order->materialRequests()->exists() || $order->stageEntries()->exists()) {
                throw ValidationException::withMessages(['order' => __('ui.workflow.material_order_locked')]);
            }

            $old = $order->load('materials.product')->toArray();
            $siblings = ProductionOrder::query()->where('company_id', $order->company_id)
                ->where('number', $order->number)->where('id', '<>', $order->id);
            $siblingIds = $siblings->pluck('id');
            $oldSiblingHeaders = $siblings->get()->map(fn (ProductionOrder $line) => [
                'id' => $line->id, 'po_date' => $line->getOriginal('po_date'), 'due_date' => $line->getOriginal('due_date'),
                'buyer_po_number' => $line->getOriginal('buyer_po_number'), 'customer_id' => $line->getOriginal('customer_id'),
                'no_cbd' => $line->getOriginal('no_cbd'), 'currency' => $line->getOriginal('currency'), 'destination' => $line->getOriginal('destination'),
            ])->all();
            if ($siblings->exists() && $data['number'] !== $order->number) {
                throw ValidationException::withMessages(['number' => __('ui.workflow.material_order_number_group_locked')]);
            }
            if ($data['number'] !== $order->number && ProductionOrder::query()->where('company_id', $order->company_id)->where('number', $data['number'])->exists()) {
                throw ValidationException::withMessages(['number' => __('ui.workflow.material_order_number_group_locked')]);
            }
            $bom = BillOfMaterial::query()->where('company_id', $this->company->id())
                ->where('product_id', $data['output_product_id'])->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $data['po_date']))
                ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $data['po_date']))
                ->with('items')->lockForUpdate()->first();

            if (!$bom || (float) $bom->output_quantity <= 0 || $bom->items->isEmpty()) {
                throw ValidationException::withMessages(['output_product_id' => __('ui.workflow.active_bom_not_found')]);
            }

            $header = [
                'buyer_po_number' => $data['buyer_po_number'] ?? null,
                'no_cbd' => $data['no_cbd'] ?? null,
                'currency' => $data['currency'],
                'destination' => $data['destination'] ?? null,
                'po_date' => $data['po_date'], 'due_date' => $data['due_date'],
                'customer_id' => $data['customer_id'],
                'planned_start_date' => $data['po_date'], 'planned_end_date' => $data['due_date'],
            ];
            if ($siblings->exists()) $siblings->update($header);
            $order->update([
                'number' => $data['number'], ...$header,
                'bill_of_material_id' => $bom->id,
                'output_product_id' => $bom->product_id, 'bom_version' => $bom->version,
                'planned_quantity' => $data['planned_quantity'],
                'price_per_unit' => $data['price_per_unit'] ?? 0,
                'total_price' => round((float) $data['planned_quantity'] * (float) ($data['price_per_unit'] ?? 0), 4),
                'description' => $data['description'] ?? null,
                'planned_start_date' => $data['po_date'], 'planned_end_date' => $data['due_date'],
            ]);
            $order->materials()->delete();
            $ratio = (float) $data['planned_quantity'] / (float) $bom->output_quantity;
            $order->materials()->createMany($bom->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'operation' => $item->operation,
                'quantity_per_output' => $item->quantity,
                'wastage_percent' => $item->wastage_percent,
                'required_quantity' => round((float) $item->quantity * $ratio * (1 + (float) $item->wastage_percent / 100), 4),
            ])->all());

            $newValues = $order->load('materials.product')->toArray();
            if ($siblingIds->isNotEmpty()) {
                $old['related_mo_lines'] = $oldSiblingHeaders;
                $newValues['related_mo_lines'] = ProductionOrder::query()->whereIn('id', $siblingIds)->get()->map(fn (ProductionOrder $line) => [
                    'id' => $line->id, 'po_date' => $line->po_date?->format('Y-m-d'), 'due_date' => $line->due_date?->format('Y-m-d'),
                    'buyer_po_number' => $line->buyer_po_number, 'customer_id' => $line->customer_id,
                    'no_cbd' => $line->no_cbd, 'currency' => $line->currency, 'destination' => $line->destination,
                ])->all();
            }

            $this->auditLog->log(
                action: 'UPDATE', module: 'MATERIAL_ORDER',
                description: __('ui.workflow.material_order_updated_audit', ['number' => $order->number]),
                model: $order, oldValues: $old,
                newValues: $newValues,
            );

            return $order->fresh(['outputProduct.unit', 'buyer', 'materials.product.unit']);
        });
    }
}

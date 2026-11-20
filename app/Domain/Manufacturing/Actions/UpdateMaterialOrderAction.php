<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\ProductionOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\Style;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateMaterialOrderAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $auditLog) {}

    public function updateImage(ProductionOrder $order, string $imagePath): ProductionOrder
    {
        return DB::transaction(function () use ($order, $imagePath) {
            $order = ProductionOrder::where('company_id', $this->company->id())->lockForUpdate()->findOrFail($order->id);
            $old = ['image_path' => $order->image_path];
            $order->update(['image_path' => $imagePath]);
            $this->auditLog->log(
                action: 'UPDATE', module: 'MATERIAL_ORDER',
                description: __('variants.image_updated_audit', ['number' => $order->number]),
                model: $order, oldValues: $old, newValues: ['image_path' => $order->image_path],
            );
            return $order;
        });
    }

    public function __invoke(ProductionOrder $order, array $data, int $userId): ProductionOrder
    {
        return DB::transaction(function () use ($order, $data, $userId) {
            $order = ProductionOrder::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($order->id);
            if ($order->materialRequests()->exists() || $order->hasOrderMaterialRequests() || $order->stageEntries()->exists()) {
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
            $productId = $data['output_product_id'] ?? null;
            $styleCode = Style::register($data['style_code'] ?? $order->model_style);
            $bom = null;
            if ($productId !== null && !empty($data['bill_of_material_id'])) {
                $bom = app(\App\Domain\Manufacturing\Services\ResolveProductionBom::class)
                    ->resolve($this->company->id(), (int) $productId, $data['po_date'], (int) $data['bill_of_material_id'], $styleCode);
            }
            $materialBasisChanged = (int) $order->output_product_id !== (int) $productId
                || $order->model_style !== $styleCode
                || $order->po_date?->toDateString() !== $data['po_date']
                || (float) $order->planned_quantity !== (float) $data['planned_quantity'];
            $replaceMaterials = $bom !== null || $materialBasisChanged;
            if ($replaceMaterials && RawMaterialOrderItem::whereIn('production_order_material_id', $order->materials()->select('id'))->exists()) {
                throw ValidationException::withMessages(['order' => __('ui.workflow.material_order_locked')]);
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
            if ($order->header) {
                $shared = collect($header)->except(['planned_start_date', 'planned_end_date'])->all();
                $changed = collect($shared)->contains(fn ($v, $k) => (string) ($order->header->getRawOriginal($k) ?? '') !== (string) ($v ?? ''));
                if ($changed && ProductionOrder::whereIn('id', $siblingIds)->where(fn ($q) => $q->whereHas('materialRequests')->orWhereHas('stageEntries'))->exists()) {
                    throw ValidationException::withMessages(['order' => __('ui.workflow.material_order_locked')]);
                }
                $order->header->update(['number' => $data['number']] + $shared);
            }
            if ($siblings->exists()) $siblings->update($header);
            $order->update([
                'number' => $data['number'], ...$header,
                'style_code' => $styleCode,
                'order_color' => $order->order_color !== null ? ($data['order_color'] ?? $order->order_color) : null,
                'order_size' => $order->order_size !== null ? ($data['order_size'] ?? $order->order_size) : null,
                ...($order->status === 'draft' && $productId !== null ? [
                    'status' => 'released', 'released_by' => $userId, 'released_at' => now(),
                ] : []),
                'output_product_id' => $productId,
                ...($replaceMaterials ? ['bill_of_material_id' => $bom?->id, 'bom_version' => $bom?->version] : []),
                'planned_quantity' => $data['planned_quantity'],
                'price_per_unit' => $data['price_per_unit'] ?? 0,
                'total_price' => round((float) $data['planned_quantity'] * (float) ($data['price_per_unit'] ?? 0), 4),
                'description' => $data['description'] ?? null,
                ...(!empty($data['image_path']) ? ['image_path' => $data['image_path']] : []),
                'planned_start_date' => $data['po_date'], 'planned_end_date' => $data['due_date'],
            ]);
            if ($replaceMaterials) {
                $order->materials()->delete();
            }
            if ($bom) {
                $ratio = (float) $data['planned_quantity'] / (float) $bom->output_quantity;
                $order->materials()->createMany($bom->items->map(fn ($item) => [
                    'bill_of_material_id' => $bom->id,
                    'product_id' => $item->product_id,
                    'operation' => $item->operation,
                    'color' => $item->applies_to_color ?? '',
                    'size' => $item->applies_to_size ?? '',
                    'quantity_per_output' => $item->quantity,
                    'wastage_percent' => $item->wastage_percent,
                    'required_quantity' => round((float) $item->quantity * $ratio * (1 + (float) $item->wastage_percent / 100), 4),
                ])->all());
            }

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

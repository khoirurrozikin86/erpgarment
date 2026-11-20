<?php

namespace App\Domain\Purchasing\Services;

use App\Models\BillOfMaterial;
use App\Models\MaterialRequest;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;

class MaterialOrderRequirements
{
    public function resolve(ProductionOrder $selected, int $companyId, ?int $bomId = null, bool $persistSnapshot = false): array
    {
        $orders = ProductionOrder::where('company_id', $companyId)
            ->when($selected->production_order_header_id,
                fn ($q) => $q->where('production_order_header_id', $selected->production_order_header_id),
                fn ($q) => $q->where('number', $selected->number))
            ->with(['materials.product.unit', 'materials.product.parentProduct', 'outputProduct', 'billOfMaterial.items'])->orderBy('id')->get();
        abort_if($orders->isEmpty(), 422, __('ui.workflow.active_bom_not_found'));

        abort_if($orders->contains(fn ($order) => !$order->output_product_id), 422, __('variants.link_finished_good'));

        $bomId ??= $selected->bill_of_material_id;
        if (!$bomId) {
            throw \Illuminate\Validation\ValidationException::withMessages(['bill_of_material_id' => __('variants.choose_bom_first')]);
        }

        $chosenBom = BillOfMaterial::withTrashed()->where('company_id', $companyId)->findOrFail($bomId);
        $orders = $orders->filter(fn (ProductionOrder $order) => $this->matches($chosenBom, $order))->values();
        abort_if($orders->isEmpty(), 422, __('ui.workflow.active_bom_not_found'));
        $snapshotsByOrder = [];
        $bomsByOrder = [];

        foreach ($orders as $order) {
            $snapshot = $order->materials->filter(fn ($material) =>
                (int) ($material->bill_of_material_id ?? $order->bill_of_material_id) === $bomId
            )->values();

            if ($snapshot->isNotEmpty()) {
                $bom = $order->billOfMaterial?->id === $bomId
                    ? $order->billOfMaterial
                    : BillOfMaterial::withTrashed()->with('items.product.unit')->findOrFail($bomId);
            } else {
                $bom = app(\App\Domain\Manufacturing\Services\ResolveProductionBom::class)
                    ->resolve($companyId, $order->output_product_id, $order->po_date->toDateString(), $bomId, $order->model_style);
                $ratio = (float) $order->planned_quantity / (float) $bom->output_quantity;
                $attributes = $bom->items->map(fn ($item) => [
                    'bill_of_material_id' => $bom->id,
                    'product_id' => $item->product_id,
                    'operation' => $item->operation,
                    'color' => $item->applies_to_color ?? '',
                    'size' => $item->applies_to_size ?? '',
                    'quantity_per_output' => $item->quantity,
                    'wastage_percent' => $item->wastage_percent,
                    'required_quantity' => round((float) $item->quantity * $ratio * (1 + (float) $item->wastage_percent / 100), 4),
                    'issued_quantity' => 0,
                    'consumed_quantity' => 0,
                ])->all();

                if ($persistSnapshot) {
                    $before = $order->materials->toArray();
                    $order->materials()->createMany($attributes);
                    $snapshot = $order->materials()->where('bill_of_material_id', $bom->id)
                        ->with(['product.unit', 'product.parentProduct'])->orderBy('id')->get();
                    app(\App\Domain\Audit\Services\AuditLogService::class)->log(
                        action: 'UPDATE', module: 'MATERIAL_ORDER', description: 'Add BOM requirements for MR: '.$order->number,
                        model: $order, oldValues: $before, newValues: $order->materials()->with('product')->get()->toArray(),
                    );
                } else {
                    $snapshot = collect($bom->items)->map(function ($item, $key) use ($attributes) {
                        $material = new ProductionOrderMaterial($attributes[$key]);
                        $material->setRelation('product', $item->product?->loadMissing(['unit', 'parentProduct']));
                        return $material;
                    });
                }
            }

            $snapshotsByOrder[$order->id] = $snapshot;
            $bomsByOrder[$order->id] = $bom;
        }

        $orderIds = $orders->pluck('id');
        $priorRequests = MaterialRequest::query()
            ->where('bill_of_material_id', $bomId)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) use ($orderIds, $selected) {
                $query->whereIn('production_order_id', $orderIds);
                if ($selected->production_order_header_id) {
                    $query->orWhere('production_order_header_id', $selected->production_order_header_id);
                }
            })
            ->with('items.product:id,parent_product_id')
            ->get();
        $reservedByProduct = [];
        foreach ($priorRequests as $request) {
            foreach ($request->items as $item) {
                $productId = $item->product?->parent_product_id ?: $item->product_id;
                $reservedByProduct[$productId] = ($reservedByProduct[$productId] ?? 0)
                    + max(0, (float) $item->quantity - (float) $item->issued_quantity);
            }
        }

        $reservedRemaining = $reservedByProduct;
        $breakdown = $orders->flatMap(function ($order) use ($snapshotsByOrder, $bomsByOrder, &$reservedRemaining) {
            $bom = $bomsByOrder[$order->id];
            return $snapshotsByOrder[$order->id]->map(function ($material) use ($order, $bom, &$reservedRemaining) {
                // Older snapshots can lack selectors; use a BOM row only when the match is unambiguous.
                $matches = $bom->items->filter(function ($item) use ($material, $order) {
                    if ($item->product_id != $material->product_id || $item->operation !== $material->operation
                        || (float) $item->quantity !== (float) $material->quantity_per_output
                        || (float) $item->wastage_percent !== (float) $material->wastage_percent) return false;
                    foreach (['color', 'size'] as $dimension) {
                        $selector = trim($item->{'applies_to_'.$dimension} ?? '');
                        if ($selector !== '' && mb_strtolower($selector) !== mb_strtolower(trim($order->outputProduct?->$dimension ?? ''))) return false;
                    }
                    return true;
                });
                $legacy = $matches->count() === 1 ? $matches->first() : null;
                $available = max(0, (float) $material->required_quantity - (float) $material->issued_quantity);
                $productId = $material->product?->parent_product_id ?: $material->product_id;
                $reserved = min($available, $reservedRemaining[$productId] ?? 0);
                $reservedRemaining[$productId] = max(0, ($reservedRemaining[$productId] ?? 0) - $reserved);

                return [
                    'production_order_id' => $order->id,
                    'style' => $order->model_style,
                    'color' => $material->color ?? ($legacy?->applies_to_color ?: $material->product?->color),
                    'size' => $material->size ?? ($legacy?->applies_to_size ?: $material->product?->size),
                    'operation' => $material->operation,
                    'order_quantity' => (float) $order->planned_quantity,
                    'product_id' => $material->product_id,
                    'code' => $material->product?->parentProduct?->code ?: $material->product?->code,
                    'name' => $material->product?->parentProduct?->name ?: $material->product?->name,
                    'unit' => $material->product?->unit?->symbol ?: $material->product?->unit?->name,
                    'consumption' => (float) $material->quantity_per_output,
                    'bom_output_quantity' => (float) ($bom->output_quantity ?: 1),
                    'allowance_percent' => (float) $material->wastage_percent,
                    'required_quantity' => (float) $material->required_quantity,
                    'issued_quantity' => (float) $material->issued_quantity,
                    'request_quantity' => round(max(0, $available - $reserved), 4),
                ];
            });
        })->values();

        $items = $breakdown->groupBy('product_id')->map(function ($materials) use ($selected) {
            $first = $materials->first();
            return [
                'product_id' => $first['product_id'],
                'quantity' => round($materials->sum('request_quantity'), 4),
                'note' => mb_substr(__('ui.workflow.production_order_material_note', [
                    'number' => $selected->number,
                    'operations' => $materials->pluck('operation')->filter()->unique()->implode(', ') ?: '—',
                ]), 0, 255),
                'label' => $first['code'].' — '.$first['name'],
                'unit' => $first['unit'],
            ];
        })->filter(fn ($item) => $item['quantity'] > 0)->values()->all();

        $selectedBom = $bomsByOrder[$orders->first()->id];
        return [
            'production_order_id' => $orders->first()->id,
            'production_order_header_id' => $selected->production_order_header_id,
            'production_order_number' => $selected->number,
            'bill_of_material_id' => $bomId,
            'output_product_id' => null,
            'bom_version' => $selectedBom->version,
            'bom_number' => $selectedBom->number,
            'bom_type' => __('variants.bom_type_'.$selectedBom->bom_type),
            'production_quantity' => (float) $orders->sum('planned_quantity'),
            'output_product' => $orders->pluck('model_style')->filter()->unique()->implode(', '),
            'source_details' => $orders->map(fn ($order) => [
                'production_order_id' => $order->id,
                'product_id' => $order->output_product_id,
                'style' => $order->model_style,
                'color' => $order->outputProduct?->color,
                'size' => $order->outputProduct?->size,
                'quantity' => (float) $order->planned_quantity,
                'bill_of_material_id' => $bomsByOrder[$order->id]->id,
                'bom_number' => $bomsByOrder[$order->id]->number,
                'bom_version' => $bomsByOrder[$order->id]->version,
                'materials' => $breakdown->where('production_order_id', $order->id)->values()->all(),
            ])->all(),
            'breakdown' => $breakdown->all(),
            'items' => $items,
        ];
    }

    public function matches(BillOfMaterial $bom, ProductionOrder $order): bool
    {
        if ((int) $order->bill_of_material_id === (int) $bom->id) {
            return true;
        }

        if ($bom->product_id) {
            return in_array((int) $bom->product_id, [(int) $order->output_product_id, (int) $order->outputProduct?->parent_product_id], true);
        }

        return filled($bom->style_code)
            && mb_strtolower(trim($bom->style_code)) === mb_strtolower(trim($order->model_style ?? ''));
    }
}

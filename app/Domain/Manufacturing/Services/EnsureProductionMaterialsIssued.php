<?php

namespace App\Domain\Manufacturing\Services;

use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use Illuminate\Validation\ValidationException;

class EnsureProductionMaterialsIssued
{
    public function __invoke(ProductionOrder $order): void
    {
        $materials = $order->materials()->fromActiveBom($order->company_id)->with('product:id,name')->get();
        if ($materials->isEmpty() && $order->materials()->exists()) {
            throw ValidationException::withMessages(['status' => __('variants.mo_material_request_required')]);
        }

        $short = $materials
            ->filter(fn ($material) => (float) $material->issued_quantity + 0.0000001 < (float) $material->required_quantity)
            ->map(fn ($material) => $material->product?->name ?? ('#' . $material->product_id))
            ->unique()->values();

        if ($short->isNotEmpty()) {
            throw ValidationException::withMessages([
                'status' => __('ui.workflow.production_materials_not_fully_issued', ['items' => $short->implode(', ')]),
            ]);
        }
    }

    public function forFabric(ProductionOrder $order, ?int $fabricProductId = null): void
    {
        $short = ProductionOrderMaterial::query()
            ->where('production_order_id', $order->id)
            ->when($fabricProductId !== null, fn ($query) => $query->where('product_id', $fabricProductId))
            ->with([
                'billOfMaterial:id,bom_type',
                'product:id,name,garment_item_type',
            ])
            ->get()
            ->filter(function (ProductionOrderMaterial $material) {
                $isFabric = $material->billOfMaterial?->bom_type === 'fabric'
                    || $material->product?->garment_item_type === 'fabric';
                return $isFabric
                    && (float) $material->issued_quantity + 0.0000001 < (float) $material->required_quantity;
            })
            ->map(fn (ProductionOrderMaterial $material) => $material->product?->name ?? ('#' . $material->product_id))
            ->unique()
            ->values();

        if ($short->isNotEmpty()) {
            throw ValidationException::withMessages([
                'status' => __('ui.workflow.production_material_fabric_not_fully_issued', [
                    'items' => $short->implode(', '),
                ]),
            ]);
        }
    }
}

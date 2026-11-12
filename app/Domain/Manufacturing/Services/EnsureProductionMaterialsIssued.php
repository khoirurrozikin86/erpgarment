<?php

namespace App\Domain\Manufacturing\Services;

use App\Models\ProductionOrder;
use Illuminate\Validation\ValidationException;

class EnsureProductionMaterialsIssued
{
    public function __invoke(ProductionOrder $order): void
    {
        $short = $order->materials()->with('product:id,name')->get()
            ->filter(fn ($material) => (float) $material->issued_quantity + 0.0000001 < (float) $material->required_quantity)
            ->map(fn ($material) => $material->product?->name ?? ('#' . $material->product_id))
            ->unique()->values();

        if ($short->isNotEmpty()) {
            throw ValidationException::withMessages([
                'status' => __('ui.workflow.production_materials_not_fully_issued', ['items' => $short->implode(', ')]),
            ]);
        }
    }
}

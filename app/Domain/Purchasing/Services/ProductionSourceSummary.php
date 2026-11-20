<?php

namespace App\Domain\Purchasing\Services;

use App\Models\MaterialRequest;
use App\Models\Product;
use Illuminate\Support\Collection;

class ProductionSourceSummary
{
    public function lines(?MaterialRequest $materialRequest): Collection
    {
        if (!$materialRequest) {
            return collect();
        }

        $details = collect($materialRequest->source_details ?? []);

        if ($details->isEmpty() && $materialRequest->productionOrderHeader) {
            $details = $materialRequest->productionOrderHeader->details->map(fn ($order) => [
                'production_order_id' => $order->id,
                'quantity' => $order->planned_quantity,
            ]);
        }

        if ($details->isNotEmpty()) {
            $headerDetails = $materialRequest->productionOrderHeader?->details->keyBy('id') ?? collect();
            $singleDetail = $details->count() === 1;

            return $details->map(function (array $detail) use ($headerDetails, $materialRequest, $singleDetail) {
                $order = $headerDetails->get($detail['production_order_id'] ?? null);
                $product = $order?->outputProduct;

                if (!$product && $singleDetail) {
                    $product = $materialRequest->productionOrder?->outputProduct ?? $materialRequest->outputProduct;
                }
                if (!$product && !empty($detail['product_id'])) {
                    $product = Product::query()->find((int) $detail['product_id']);
                }

                $detailStyle = trim((string) ($detail['style'] ?? ''));
                $detailColor = trim((string) ($detail['color'] ?? ''));
                $detailSize = trim((string) ($detail['size'] ?? ''));

                return [
                    'style' => $detailStyle ?: trim((string) ($product?->style_code ?? '')) ?: null,
                    'color' => $detailColor ?: trim((string) ($product?->color ?? '')) ?: null,
                    'size' => $detailSize ?: trim((string) ($product?->size ?? '')) ?: null,
                    'quantity' => isset($detail['quantity']) ? (float) $detail['quantity'] : null,
                    'code' => $product?->code,
                    'name' => $product?->name,
                ];
            })->filter(fn (array $line) => $line['style'] || $line['quantity'] !== null)->values();
        }

        $order = $materialRequest->productionOrder;
        $product = $order?->outputProduct ?? $materialRequest->outputProduct;
        $quantity = $order?->planned_quantity ?? $materialRequest->production_quantity;

        if (!$product && $quantity === null) {
            return collect();
        }

        return collect([[
            'style' => $product?->style_code,
            'color' => $product?->color,
            'size' => $product?->size,
            'quantity' => $quantity !== null ? (float) $quantity : null,
            'code' => $product?->code,
            'name' => $product?->name,
        ]]);
    }

    public function total(?MaterialRequest $materialRequest): ?float
    {
        $lines = $this->lines($materialRequest);
        if ($lines->isEmpty()) {
            return null;
        }

        $quantities = $lines->pluck('quantity')->filter(fn ($quantity) => $quantity !== null);
        return $quantities->isEmpty() ? null : (float) $quantities->sum();
    }
}

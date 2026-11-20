<?php

namespace App\Domain\Inventory\Queries;

use App\Models\StockMovement;
use Illuminate\Support\Collection;

class StockMovementDimensions
{
    public static function pairs(Collection $movements): Collection
    {
        return $movements->map(function (StockMovement $movement) {
            $issue = $movement->materialIssueItem;
            $raw = $issue?->rawMaterialOrderItem;
            $request = $issue?->requestItem ?? $movement->materialRequestItem;
            $receipt = $movement->goodsReceiptItem ?? $movement->purchaseReturnItem?->goodsReceiptItem;
            $receiptRequest = $receipt?->purchaseOrderItem?->purchaseRequestItem?->materialRequestItem;

            $order = $movement->productionOrderStageEntry?->productionOrder;
            $countedSku = $movement->stockOpnameItem?->product;

            return [
                'color' => collect([$raw?->color, $raw?->sourceMaterial?->color, $request?->color, $receiptRequest?->color, $order?->order_color, $countedSku?->color])->first(fn ($value) => filled($value)),
                'size' => collect([$raw?->size, $raw?->sourceMaterial?->size, $request?->size, $receiptRequest?->size, $order?->order_size, $countedSku?->size])->first(fn ($value) => filled($value)),
            ];
        })->filter(fn ($pair) => filled($pair['color']) || filled($pair['size']))
            ->unique(fn ($pair) => json_encode($pair))
            ->sortBy(fn ($pair) => ($pair['color'] ?? '').' '.($pair['size'] ?? ''), SORT_NATURAL)
            ->values();
    }

    public static function summarize(Collection $movements): array
    {
        $pairs = self::pairs($movements);
        if ($pairs->isEmpty()) {
            return ['color' => '—', 'size' => '—'];
        }

        return [
            'color' => $pairs->map(fn ($pair) => $pair['color'] ?? '—')->implode(' / '),
            'size' => $pairs->map(fn ($pair) => $pair['size'] ?? '—')->implode(' / '),
        ];
    }
}

<?php

namespace App\Domain\Purchasing\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MaterialRequestLineAggregator
{
    public function forStorage(array $items): array
    {
        return collect($items)->map(fn (array $item) => [
            'product_id' => (int) $item['product_id'],
            'color' => trim($item['color'] ?? '') ?: null,
            'size' => trim($item['size'] ?? '') ?: null,
            'quantity' => (float) $item['quantity'],
            'note' => trim($item['note'] ?? '') ?: null,
        ])->groupBy(function (array $item, mixed $index) {
            if ($item['color'] !== null || $item['size'] !== null) {
                return 'line:'.$index;
            }

            return 'global:'.$item['product_id'];
        })->map(function (Collection $group) {
            $item = $group->first();
            $notes = $group->pluck('note')->filter()->unique()->values();
            $item['quantity'] = round($group->sum('quantity'), 4);
            $item['note'] = $notes->implode(' / ') ?: null;

            if (mb_strlen($item['note'] ?? '') > 255) {
                throw ValidationException::withMessages([
                    'items' => __('validation.max.string', ['attribute' => __('ui.common.notes'), 'max' => 255]),
                ]);
            }

            return $item;
        })->values()->all();
    }
}

<?php

namespace App\Domain\Purchasing\Services;

use Illuminate\Support\Collection;

class MaterialRequestPdfItems
{
    public function forPdf(Collection $items): Collection
    {
        $details = $items->map(function ($item) {
            $product = $item->product;

            return [
                'code' => $product?->code ?? '—',
                'name' => $product?->name ?? 'Barang dihapus',
                'color' => trim((string) ($item->color ?: $product?->color)) ?: null,
                'size' => trim((string) ($item->size ?: $product?->size)) ?: null,
                'quantity' => (float) $item->quantity,
                'unit' => $product?->unit?->symbol ?: $product?->unit?->name ?: '—',
                'note' => trim((string) $item->note) ?: null,
                'deleted_item_id' => $product ? null : $item->id,
            ];
        })->groupBy(fn (array $item) => json_encode([
            $item['deleted_item_id'],
            $item['code'],
            $item['name'],
            $item['color'],
            $item['size'],
            $item['unit'],
        ], JSON_THROW_ON_ERROR))->map(function (Collection $group) {
            $item = $group->first();
            $item['quantity'] = $group->sum('quantity');
            $item['note'] = $group->pluck('note')->filter()->unique()->implode('; ') ?: '—';

            return $item;
        })->sortBy(fn (array $item) => $this->variantSortKey($item))->values();

        return $details;
    }

    private function variantSortKey(array $item): string
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '2XL', '3XL', '4XL', '5XL'];
        $size = strtoupper(trim((string) $item['size']));
        $sizeOrder = array_search($size, $sizes, true);

        return mb_strtolower((string) $item['color']).'|'
            .sprintf('%03d', $sizeOrder === false ? count($sizes) : $sizeOrder).'|'
            .mb_strtolower($size);
    }
}

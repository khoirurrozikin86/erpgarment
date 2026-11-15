<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EquipmentStockCardProductsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $products) {}

    public function collection(): Collection
    {
        return $this->products;
    }

    public function headings(): array
    {
        return [__('ui.common.item_code'), __('ui.common.item_name'), __('ui.common.category'), __('ui.common.unit')];
    }

    public function map($product): array
    {
        return [
            $product->code,
            $product->name,
            $product->category?->name ?: $product->category?->code ?: '—',
            $product->unit?->symbol ?: $product->unit?->name ?: '—',
        ];
    }
}

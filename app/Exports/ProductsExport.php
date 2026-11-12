<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return Product::query()->with(['category', 'unit'])->orderBy('name');
    }

    public function headings(): array
    {
        return ['Kategori', 'Kode Barang', 'Nama Barang', 'Satuan', 'Kode Style', 'Warna', 'Ukuran', 'Available'];
    }

    public function map($product): array
    {
        return [
            $product->category?->name,
            $product->code,
            $product->name,
            $product->unit
                ? $product->unit->name . ($product->unit->symbol ? " ({$product->unit->symbol})" : '')
                : null,
            $product->style_code,
            $product->color,
            $product->size,
            $product->is_available ? 'Available' : 'Unavailable',
        ];
    }
}

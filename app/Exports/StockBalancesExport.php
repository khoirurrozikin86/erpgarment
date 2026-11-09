<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\StockBalanceQuery;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockBalancesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return app(StockBalanceQuery::class)->builder();
    }

    public function headings(): array
    {
        return ['Kode Barang', 'Nama Barang', 'Kategori', 'Satuan', 'Saldo Stok', 'Reorder Point', 'Status'];
    }

    public function map($product): array
    {
        /** @var Product $product */
        $quantity = (float) ($product->stock?->quantity ?? 0);
        $reorderPoint = (float) $product->reorder_point;
        $threshold = $reorderPoint > 0 ? $reorderPoint : (float) $product->min_stock;
        $status = $quantity <= 0 && $threshold > 0
            ? 'Habis'
            : ($threshold > 0 && $quantity <= $threshold ? 'Stok Rendah' : 'Aman');

        return [
            $product->code,
            $product->name,
            $product->category?->name,
            $product->unit?->symbol ?: $product->unit?->name,
            $quantity,
            $threshold,
            $status,
        ];
    }
}

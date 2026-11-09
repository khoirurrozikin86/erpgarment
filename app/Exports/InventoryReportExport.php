<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\InventoryReportQuery;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private string $fromDate, private string $toDate) {}

    public function query()
    {
        return app(InventoryReportQuery::class)->builder($this->fromDate, $this->toDate);
    }

    public function headings(): array
    {
        return ['Kode Barang', 'Nama Barang', 'Kategori', 'Satuan', 'Saldo Awal', 'Barang Masuk', 'Barang Keluar', 'Saldo Akhir', 'Batas Minimum'];
    }

    public function map($product): array
    {
        /** @var Product $product */
        $opening = (float) ($product->opening_balance ?? 0);
        $incoming = (float) ($product->quantity_in ?? 0);
        $outgoing = abs((float) ($product->quantity_out ?? 0));
        return [
            $product->code, $product->name, $product->category?->name,
            $product->unit?->symbol ?: $product->unit?->name,
            $opening, $incoming, $outgoing, $opening + $incoming - $outgoing,
            (float) ($product->reorder_point > 0 ? $product->reorder_point : $product->min_stock),
        ];
    }
}

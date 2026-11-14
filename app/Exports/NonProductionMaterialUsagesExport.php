<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\NonProductionMaterialUsageTableQuery;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class NonProductionMaterialUsagesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private int $companyId, private ?string $fromDate = null, private ?string $toDate = null) {}

    public function query(): Builder
    {
        return app(NonProductionMaterialUsageTableQuery::class)->builder($this->companyId, $this->fromDate, $this->toDate);
    }

    public function headings(): array
    {
        return ['No. Transaksi', 'Tanggal Transaksi', 'Style', 'Kode Barang', 'Nama Barang', 'Satuan', 'Qty Pakai', 'Harga', 'Total Harga'];
    }

    public function map($item): array
    {
        return [
            $item->document?->number,
            $item->document?->transaction_date?->format('Y-m-d'),
            $item->product?->style_code ?: '—',
            $item->product?->code,
            $item->product?->name,
            $item->product?->unit?->symbol ?: $item->product?->unit?->name,
            (float) $item->quantity,
            (float) $item->unit_price,
            (float) $item->total_price,
        ];
    }
}

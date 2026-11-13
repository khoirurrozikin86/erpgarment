<?php

namespace App\Exports;

use App\Domain\Manufacturing\Queries\RawMaterialOrderTableQuery;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RawMaterialOrdersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    private int $row = 0;
    public function __construct(private readonly int $companyId, private readonly ?string $fromDate = null, private readonly ?string $toDate = null) {}
    public function query(): Builder { return (new RawMaterialOrderTableQuery())->rows($this->companyId, $this->fromDate, $this->toDate); }
    public function headings(): array { return ['No.', 'No. Order Bahan Baku', 'Tanggal Order', 'No. MO', 'Style', 'Kode Barang', 'Nama Barang', 'Satuan', 'Jumlah Order', 'Jumlah Terkirim']; }
    public function map($item): array
    {
        return [++$this->row, $item->number, $item->document?->order_date?->format('Y-m-d'), $item->document?->production_order_number, $item->style_code, $item->product?->code, $item->product?->name, $item->product?->unit?->symbol ?: $item->product?->unit?->name, (float) $item->quantity, (float) $item->issued_quantity];
    }
}

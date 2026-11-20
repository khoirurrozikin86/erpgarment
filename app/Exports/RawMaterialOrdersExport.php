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
    public function headings(): array { return ['No.', 'No. Order Bahan Baku', 'Tanggal Order', 'No. MO', 'Style', 'Kategori', 'Kode Barang', 'Nama Barang', 'Color', 'Size', 'Satuan', 'Jumlah Order', 'Jumlah Terkirim']; }
    public function map($item): array
    {
        return [++$this->row, $item->document?->number ?: $item->number, $item->document?->order_date?->format('Y-m-d'), $item->document?->production_order_number ?: 'Manual', $item->style_code, match ($item->category) { 'fabric' => 'Fabric', 'accessory' => 'Aksesoris', 'general' => 'Umum', default => '—' }, $item->product?->code, $item->product?->name, $item->color ?: $item->sourceMaterial?->color ?: $item->product?->color ?: '—', $item->size ?: $item->sourceMaterial?->size ?: $item->product?->size ?: '—', $item->product?->unit?->symbol ?: $item->product?->unit?->name, (float) $item->quantity, (float) $item->issued_quantity];
    }
}

<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\MaterialIssueTableQuery;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MaterialIssuesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    private int $row = 0;
    public function __construct(private readonly int $companyId, private readonly ?string $fromDate = null, private readonly ?string $toDate = null) {}
    public function query(): Builder { return (new MaterialIssueTableQuery())->history($this->companyId, $this->fromDate, $this->toDate); }
    public function headings(): array { return ['No.', 'No. MIS', 'Tanggal MIS', 'No. Permintaan Produksi', 'No. MO', 'Kode Barang', 'Nama Barang', 'Satuan', 'Jumlah Kirim']; }
    public function map($item): array
    {
        return [++$this->row, $item->document?->number, $item->document?->issue_date?->format('Y-m-d'), $item->rawMaterialOrderItem?->number ?: $item->document?->request?->number, $item->rawMaterialOrderItem?->sourceMaterial?->productionOrder?->number ?: $item->document?->rawMaterialOrder?->production_order_number ?: $item->document?->request?->productionOrder?->number, $item->product?->code, $item->product?->name, $item->product?->unit?->symbol ?: $item->product?->unit?->name, (float) $item->quantity];
    }
}

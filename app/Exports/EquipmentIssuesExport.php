<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\EquipmentIssueTableQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping};

class EquipmentIssuesExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private int $companyId, private ?string $from, private ?string $to) {}
    public function query() { return app(EquipmentIssueTableQuery::class)->documents($this->companyId, $this->from, $this->to); }
    public function headings(): array { return ['No Pengeluaran Mesin', 'Tanggal Pengeluaran Mesin', 'Kode Barang', 'Nama Barang', 'Satuan', 'Jumlah', 'Keterangan']; }
    public function map($row): array { $i = $row->item; return [$row->number, $row->issue_date?->format('Y-m-d'), $i?->item_code, $i?->item_name, $i?->unit, $i?->quantity, $i?->description]; }
}

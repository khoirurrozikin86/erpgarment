<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\WarehouseReturnReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class WarehouseReturnReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting
{
    public function __construct(private int $companyId, private array $filters) {}
    public function query() { return app(WarehouseReturnReportQuery::class)->builder($this->companyId, $this->filters); }
    public function headings(): array { return ['No Reject', 'Tanggal', 'Kode Barang', 'Nama Barang', 'Satuan', 'Jumlah']; }
    public function map($row): array
    {
        return [$row->document?->number, $row->document?->return_date?->format('Y-m-d'), $row->product?->code, $row->product?->name, $row->product?->unit?->symbol ?: $row->product?->unit?->name, (float) $row->quantity];
    }
    public function columnFormats(): array { return ['F' => '#,##0.0000']; }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

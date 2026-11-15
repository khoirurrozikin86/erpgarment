<?php

namespace App\Exports;

use App\Domain\Manufacturing\Queries\WipPositionReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class WipPositionReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting
{
    public function __construct(private int $companyId, private array $filters) {}
    public function query() { return app(WipPositionReportQuery::class)->builder($this->companyId, $this->filters); }
    public function headings(): array { return ['Kode Barang', 'Nama Barang', 'Satuan Barang', 'Jumlah Barang', 'Keterangan']; }
    public function map($row): array
    {
        return [$row->code.'/'.$row->position, $row->name, $row->symbol ?: $row->unit_name, (float) $row->quantity, (float) $row->quantity < 0 ? 'Periksa urutan atau koreksi transaksi produksi' : null];
    }
    public function columnFormats(): array { return ['D' => '#,##0.####']; }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

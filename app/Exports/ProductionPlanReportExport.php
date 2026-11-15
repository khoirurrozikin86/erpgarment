<?php

namespace App\Exports;

use App\Domain\Manufacturing\Queries\ProductionPlanReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class ProductionPlanReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting
{
    public function __construct(private int $companyId, private array $filters) {}
    public function query() { return app(ProductionPlanReportQuery::class)->builder($this->companyId, $this->filters); }
    public function headings(): array { return ['No Rencana Produksi', 'Tanggal', 'Kode Barang', 'Nama Barang', 'Jumlah Order', 'Jumlah Reject', 'Jumlah Prod', 'Jumlah Export']; }
    public function map($row): array
    {
        return [$row->number, $row->po_date?->format('d-m-Y'), $row->outputProduct?->code, $row->outputProduct?->name, (float) $row->planned_quantity, (float) ($row->report_rejected ?? 0), (float) ($row->report_produced ?? 0), '—'];
    }
    public function columnFormats(): array { return ['E' => '#,##0.0000', 'F' => '#,##0.####', 'G' => '#,##0.####']; }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

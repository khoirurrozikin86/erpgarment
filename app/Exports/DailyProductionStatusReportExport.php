<?php

namespace App\Exports;

use App\Domain\Manufacturing\Queries\DailyProductionStatusReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class DailyProductionStatusReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting
{
    public function __construct(private int $companyId, private array $filters) {}
    public function query() { return app(DailyProductionStatusReportQuery::class)->builder($this->companyId, $this->filters); }
    public function headings(): array
    {
        return ['Buyer', 'No Planning', 'PO Buyer', 'Style', 'Kode Barang', 'Nama Barang', 'Jumlah Order (1)', 'Cutting Today (2)', 'Cutting Total (3)', '4 = 3 - 1', 'Sewing Today (5)', 'Sewing Total (6)', '7 = 6 - 3', 'Finishing Today (8)', 'Finishing Total (9)', '10 = 9 - 6', 'Delivery Today (11)', 'Delivery Total (12)'];
    }
    public function map($row): array
    {
        return [$row->buyer?->name ?? '—', $row->number, $row->buyer_po_number ?? '—', $row->outputProduct?->style_code ?? '—', $row->outputProduct?->code ?? '—', $row->outputProduct?->name ?? '—', (float) $row->planned_quantity, ...DailyProductionStatusReportQuery::values($row)];
    }
    public function columnFormats(): array { return array_fill_keys(range('G', 'R'), '#,##0.####'); }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

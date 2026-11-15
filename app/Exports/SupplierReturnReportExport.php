<?php

namespace App\Exports;

use App\Domain\Purchasing\Queries\SupplierReturnReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class SupplierReturnReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting
{
    public function __construct(private array $filters) {}
    public function query() { return app(SupplierReturnReportQuery::class)->builder($this->filters); }
    public function headings(): array { return [__('bc_reports.return_no'), __('bc_reports.date'), __('bc_reports.item_code'), __('bc_reports.item_name'), __('bc_reports.unit'), __('bc_reports.quantity')]; }
    public function map($row): array
    {
        return [$row->purchaseReturn?->number, $row->purchaseReturn?->returned_at?->format('Y-m-d'), $row->product?->code, $row->product?->name, $row->product?->unit?->symbol ?: $row->product?->unit?->name, (float) $row->quantity];
    }
    public function columnFormats(): array { return ['F' => '#,##0.0000']; }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

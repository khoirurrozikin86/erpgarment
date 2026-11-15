<?php

namespace App\Exports;

use App\Domain\Manufacturing\Queries\ProductionResultReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class ProductionResultReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting
{
    public function __construct(private int $companyId, private array $filters) {}
    public function query() { return app(ProductionResultReportQuery::class)->builder($this->companyId, $this->filters); }
    public function headings(): array { return [__('bc_reports.planning_no'), __('bc_reports.buyer_po'), __('bc_reports.production_no'), __('bc_reports.date'), __('bc_reports.item_code'), __('bc_reports.item_name'), __('bc_reports.unit'), __('bc_reports.received_from_sewing'), __('bc_reports.production_output'), __('bc_reports.warehouse')]; }
    public function map($row): array
    {
        $product = $row->productionOrder?->outputProduct;
        return [$row->productionOrder?->number, $row->productionOrder?->buyer_po_number, $row->packing_number, $row->processed_at?->format('d-m-Y'), $product?->code, $product?->name, $product?->unit?->symbol ?: $product?->unit?->name, (float) $row->input_quantity, (float) $row->good_quantity, '—'];
    }
    public function columnFormats(): array { return ['H' => '#,##0.####', 'I' => '#,##0.####']; }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

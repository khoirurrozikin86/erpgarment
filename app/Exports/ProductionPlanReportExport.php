<?php

namespace App\Exports;

use App\Domain\Manufacturing\Queries\ProductionPlanReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting, WithStrictNullComparison};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class ProductionPlanReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting, WithStrictNullComparison
{
    public function __construct(private int $companyId, private array $filters) {}
    public function query() { return app(ProductionPlanReportQuery::class)->builder($this->companyId, $this->filters); }
    public function headings(): array { return [__('bc_reports.production_plan_no'), __('bc_reports.date'), __('bc_reports.item_code'), __('bc_reports.item_name'), __('ui.workflow.color'), __('ui.workflow.size'), __('bc_reports.order_quantity'), __('bc_reports.rejected_quantity'), __('bc_reports.produced_quantity'), __('ui.gsn.quantity')]; }
    public function map($row): array
    {
        return [$row->number, $row->po_date?->format('d-m-Y'), $row->outputProduct?->code, $row->outputProduct?->name, $row->outputProduct?->color, $row->outputProduct?->size, (float) $row->planned_quantity, (float) ($row->report_rejected ?? 0), (float) ($row->report_produced ?? 0), (float) ($row->report_shipped ?? 0)];
    }
    public function columnFormats(): array { return ['G' => '#,##0.####', 'H' => '#,##0.####', 'I' => '#,##0.####', 'J' => '#,##0.####']; }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

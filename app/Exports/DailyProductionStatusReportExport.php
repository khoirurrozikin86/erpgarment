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
        return [__('bc_reports.buyer'), __('bc_reports.planning_no'), __('bc_reports.buyer_po'), __('bc_reports.style'), __('bc_reports.item_code'), __('bc_reports.item_name'), __('ui.workflow.color'), __('ui.workflow.size'), __('bc_reports.order_quantity_1'), __('bc_reports.cutting_today_export'), __('bc_reports.cutting_total_export'), '4 = 3 - 1', __('bc_reports.sewing_today_export'), __('bc_reports.sewing_total_export'), '7 = 6 - 3', __('ui.packing.title').' '.__('variants.stage_today'), __('ui.packing.title').' '.__('variants.stage_total'), __('bc_reports.finishing_today_export'), __('bc_reports.finishing_total_export'), '10 = 9 - 6', __('bc_reports.delivery_today_export'), __('bc_reports.delivery_total_export')];
    }
    public function map($row): array
    {
        return [$row->buyer?->name ?? '—', $row->number, $row->buyer_po_number ?? '—', $row->outputProduct?->style_code ?? '—', $row->outputProduct?->code ?? '—', $row->outputProduct?->name ?? '—', $row->outputProduct?->color, $row->outputProduct?->size, (float) $row->planned_quantity, ...DailyProductionStatusReportQuery::values($row)];
    }
    public function columnFormats(): array { return array_fill_keys(range('I', 'V'), '#,##0.####'); }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

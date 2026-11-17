<?php

namespace App\Exports;

use App\Domain\Manufacturing\Queries\WipPositionReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class WipPositionReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting
{
    public function __construct(private int $companyId, private array $filters) {}
    public function query() { return app(WipPositionReportQuery::class)->builder($this->companyId, $this->filters); }
    public function headings(): array { return [__('bc_reports.planning_no'), __('bc_reports.style'), __('bc_reports.item_code'), __('bc_reports.item_name'), __('ui.workflow.color'), __('ui.workflow.size'), __('bc_reports.order_quantity'), __('bc_reports.item_unit'), __('bc_reports.item_quantity'), __('bc_reports.remarks')]; }
    public function map($row): array
    {
        return [$row->mo_number, $row->style_code, $row->code.'/'.$row->position, $row->name, $row->color, $row->size, (float) $row->planned_quantity, $row->symbol ?: $row->unit_name, (float) $row->quantity, (float) $row->quantity < 0 ? __('bc_reports.review_production_transaction_order_or_corrections') : null];
    }
    public function columnFormats(): array { return ['G' => '#,##0.####', 'I' => '#,##0.####']; }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

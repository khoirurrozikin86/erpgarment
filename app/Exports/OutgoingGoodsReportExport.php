<?php

namespace App\Exports;

use App\Domain\Purchasing\Queries\OutgoingGoodsReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class OutgoingGoodsReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder
{
    public function __construct(private int $companyId, private array $filters) {}
    public function query() { return app(OutgoingGoodsReportQuery::class)->builder($this->companyId, $this->filters); }
    public function headings(): array { return [__('bc_reports.customs_document_type'), __('bc_reports.registration_no'), __('bc_reports.registration_date'), __('bc_reports.issue_no'), __('bc_reports.issue_date'), __('bc_reports.recipient'), __('bc_reports.hs_code'), __('bc_reports.item_code'), __('bc_reports.item_name'), __('bc_reports.item_unit'), __('bc_reports.item_quantity'), __('bc_reports.unit_price'), __('bc_reports.total_amount')]; }
    public function map($row): array
    {
        return [null, null, null, $row->number, $row->document_date, $row->recipient, null, $row->item_code, $row->item_name, $row->unit, (float) $row->quantity, null, null];
    }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

<?php

namespace App\Exports;

use App\Domain\Purchasing\Queries\IncomingGoodsReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

class IncomingGoodsReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder
{
    public function __construct(private array $filters) {}
    public function query() { return app(IncomingGoodsReportQuery::class)->builder($this->filters); }
    public function headings(): array { return [__('bc_reports.customs_document_type'), __('bc_reports.registration_no'), __('bc_reports.registration_date'), __('bc_reports.receipt_no'), __('bc_reports.receipt_date'), __('bc_reports.sender'), __('bc_reports.hs_code'), __('variants.stock_parent_code'), __('variants.stock_sku'), __('bc_reports.item_name'), __('ui.common.category'), __('ui.workflow.color'), __('ui.workflow.size'), __('bc_reports.item_unit'), __('bc_reports.item_quantity'), __('bc_reports.unit_price'), __('bc_reports.total_amount'), __('bc_reports.currency')]; }
    public function map($row): array
    {
        $receipt = $row->goodsReceipt;
        return [$receipt?->customs_document_code, $receipt?->customs_document_number, $receipt?->customs_document_date?->format('d-m-Y'), $receipt?->number, $receipt?->received_at?->format('d-m-Y'), $receipt?->supplier?->name, '—', $row->product?->parentProduct?->code ?? $row->product?->code, $row->product?->code, $row->product?->name, $row->product?->category?->name, $row->received_color, $row->received_size, $row->product?->unit?->symbol ?: $row->product?->unit?->name, (float) $row->quantity, (float) $row->unit_price, (float) $row->line_total, $receipt?->currency];
    }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

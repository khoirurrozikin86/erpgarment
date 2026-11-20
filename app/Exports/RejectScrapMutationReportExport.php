<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\RejectScrapMutationReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class RejectScrapMutationReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting
{
    public function __construct(private array $filters) {}
    public function query() { return app(RejectScrapMutationReportQuery::class)->builder($this->filters); }
    public function headings(): array
    {
        return [__('bc_reports.item_code'), __('bc_reports.item_name'), __('ui.common.category'), __('bc_reports.item_unit'), __('bc_reports.item_quantity'), __('bc_reports.opening_balance'), __('bc_reports.incoming_quantity'), __('bc_reports.outgoing_quantity'), __('bc_reports.adjustment'), __('bc_reports.closing_balance'), __('bc_reports.physical_count'), __('bc_reports.difference'), __('bc_reports.remarks')];
    }
    public function map($product): array
    {
        $v = RejectScrapMutationReportQuery::values($product);
        return [$product->code, $product->name, $product->category?->name, $product->unit?->symbol ?: $product->unit?->name, null, $v['opening'], $v['incoming'], $v['outgoing'], $v['adjustment'], $v['closing'], $v['counted'], $v['difference'], $v['notes']];
    }
    public function columnFormats(): array { return array_fill_keys(range('E', 'L'), '#,##0.##'); }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

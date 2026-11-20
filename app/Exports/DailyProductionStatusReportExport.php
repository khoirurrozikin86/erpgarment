<?php

namespace App\Exports;

use App\Domain\Manufacturing\Queries\DailyProductionStatusReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting, WithStrictNullComparison};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class DailyProductionStatusReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting, WithStrictNullComparison
{
    public function __construct(private int $companyId, private array $filters) {}
    public function query() { return app(DailyProductionStatusReportQuery::class)->builder($this->companyId, $this->filters); }
    public function headings(): array
    {
        return [
            __('bc_reports.buyer'),
            __('bc_reports.planning_no'),
            __('bc_reports.buyer_po'),
            __('bc_reports.style'),
            __('variants.stock_parent_code'),
            __('variants.stock_sku'),
            __('bc_reports.item_name'),
            __('ui.workflow.color'),
            __('ui.workflow.size'),
            __('bc_reports.cutting'),
            __('bc_reports.sewing'),
            __('ui.packing.title'),
            __('bc_reports.finishing'),
        ];
    }
    public function map($row): array
    {
        $product = $row->outputProduct;
        return [$row->buyer?->name ?? '—', $row->number, $row->buyer_po_number ?? '—', $row->model_style ?? '—', $product?->parentProduct?->code ?? $product?->code, $product?->parent_product_id ? $product->code : null, $product?->name ?? '—', $row->line_color, $row->line_size, ...DailyProductionStatusReportQuery::values($row)];
    }
    public function columnFormats(): array { return array_fill_keys(range('J', 'M'), '#,##0.####'); }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\WarehouseReturnReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class WarehouseReturnReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting
{
    public function __construct(private int $companyId, private array $filters) {}
    public function query() { return app(WarehouseReturnReportQuery::class)->builder($this->companyId, $this->filters); }
    public function headings(): array { return [__('bc_reports.reject_no'), __('bc_reports.date'), __('variants.stock_parent_code'), __('variants.stock_sku'), __('bc_reports.item_name'), __('ui.workflow.color'), __('ui.workflow.size'), __('bc_reports.unit'), __('bc_reports.quantity')]; }
    public function map($row): array
    {
        $product = $row->product;
        return [$row->document?->number, $row->document?->return_date?->format('Y-m-d'), $product?->parentProduct?->code ?? $product?->code, $product?->parent_product_id ? $product->code : null, $product?->name, $product?->parent_product_id ? $product->color : null, $product?->parent_product_id ? $product->size : null, $product?->unit?->symbol ?: $product?->unit?->name, (float) $row->quantity];
    }
    public function columnFormats(): array { return ['I' => '#,##0.0000']; }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

<?php

namespace App\Exports;

use App\Domain\Purchasing\Queries\SupplierReturnReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class SupplierReturnReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting
{
    public function __construct(private array $filters) {}
    public function query() { return app(SupplierReturnReportQuery::class)->builder($this->filters); }
    public function headings(): array { return [__('bc_reports.return_no'), __('bc_reports.date'), __('variants.stock_parent_code'), __('variants.stock_sku'), __('bc_reports.item_name'), __('ui.workflow.color'), __('ui.workflow.size'), __('bc_reports.unit'), __('bc_reports.quantity')]; }
    public function map($row): array
    {
        $product = $row->product;
        return [$row->purchaseReturn?->number, $row->purchaseReturn?->returned_at?->format('Y-m-d'), $product?->parentProduct?->code ?? $product?->code, $product?->parent_product_id ? $product->code : null, $product?->name, $row->goodsReceiptItem?->received_color ?? $product?->color, $row->goodsReceiptItem?->received_size ?? $product?->size, $product?->unit?->symbol ?: $product?->unit?->name, (float) $row->quantity];
    }
    public function columnFormats(): array { return ['I' => '#,##0.####']; }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

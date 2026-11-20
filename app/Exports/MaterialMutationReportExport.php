<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\MaterialMutationReportQuery;
use App\Domain\Inventory\Queries\StockMovementDimensions;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class MaterialMutationReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting
{
    public function __construct(private array $filters) {}
    public function query() { return app(MaterialMutationReportQuery::class)->builder($this->filters); }
    public function headings(): array
    {
        return [__('variants.stock_parent_code'), __('variants.stock_sku'), __('bc_reports.item_name'), __('ui.common.category'), __('ui.workflow.color'), __('ui.workflow.size'), __('bc_reports.item_unit'), __('bc_reports.item_quantity'), __('bc_reports.opening_balance'), __('bc_reports.incoming_quantity'), __('bc_reports.outgoing_quantity'), __('bc_reports.adjustment'), __('bc_reports.closing_balance'), __('bc_reports.material_stocktake_quantity'), __('bc_reports.difference'), __('bc_reports.remarks')];
    }
    public function map($product): array
    {
        $v = MaterialMutationReportQuery::values($product);
        $dimensions = StockMovementDimensions::summarize($product->stockMovements);
        return [$product->parentProduct?->code ?? $product->code, $product->code, $product->name, $product->category?->name, $dimensions['color'], $dimensions['size'], $product->unit?->symbol ?: $product->unit?->name, null, $v['opening'], $v['incoming'], $v['outgoing'], $v['adjustment'], $v['closing'], $v['counted'], $v['difference'], $v['notes']];
    }
    public function columnFormats(): array { return array_fill_keys(range('H', 'O'), '#,##0.##'); }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

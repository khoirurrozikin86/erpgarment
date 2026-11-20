<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\ProductionReturnTableQuery;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{FromQuery, ShouldAutoSize, WithHeadings, WithMapping};

class ProductionReturnExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private int $companyId, private ?string $from = null, private ?string $to = null) {}
    public function query(): Builder { return app(ProductionReturnTableQuery::class)->items($this->companyId,$this->from,$this->to); }
    public function headings(): array { return ['No Return','Return Date','No MO','Style','No PO Supplier',__('ui.gsn.parent_code'), __('ui.gsn.sku_code'),'Item Name', __('ui.workflow.color'), __('ui.workflow.size'), 'Unit','Qty Return','Description','Status']; }
    public function map($item): array { return [$item->document?->number,$item->document?->return_date?->format('Y-m-d'),$item->document?->rawMaterialOrder?->number,$item->product?->style_code,$item->document?->po_supplier_number,$item->product?->parentProduct?->code ?? $item->product?->code,$item->product?->parent_product_id ? $item->product->code : null,$item->product?->name,$item->product?->color,$item->product?->size,$item->product?->unit?->symbol ?: $item->product?->unit?->name,(float)$item->quantity,$item->description,$item->document?->status]; }
}

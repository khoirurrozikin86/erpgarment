<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\ProductionReturnTableQuery;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{FromQuery, ShouldAutoSize, WithHeadings, WithMapping};

class ProductionReturnExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private int $companyId, private ?string $from = null, private ?string $to = null) {}
    public function query(): Builder { return app(ProductionReturnTableQuery::class)->items($this->companyId,$this->from,$this->to); }
    public function headings(): array { return ['No Return','Return Date','No MO','Style','No PO Supplier','Item Code','Item Name','Unit','Qty Return','Description','Status']; }
    public function map($item): array { return [$item->document?->number,$item->document?->return_date?->format('Y-m-d'),$item->document?->rawMaterialOrder?->number,$item->product?->style_code,$item->document?->po_supplier_number,$item->product?->code,$item->product?->name,$item->product?->unit?->symbol ?: $item->product?->unit?->name,(float)$item->quantity,$item->description,$item->document?->status]; }
}

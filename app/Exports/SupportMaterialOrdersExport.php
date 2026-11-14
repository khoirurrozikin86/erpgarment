<?php
namespace App\Exports;

use App\Domain\Manufacturing\Queries\SupportMaterialOrderTableQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping};

class SupportMaterialOrdersExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private int $companyId, private ?string $from, private ?string $to) {}
    public function query() { return app(SupportMaterialOrderTableQuery::class)->builder($this->companyId, $this->from, $this->to); }
    public function headings(): array { return ['No Order', 'Order Date', 'Style', 'Item Code', 'Item Name', 'Unit', 'Qty Order', 'No PO Other']; }
    public function map($row): array { return [$row->document?->number, $row->document?->order_date?->format('Y-m-d'), $row->style, $row->product?->code, $row->product?->name, $row->product?->unit?->symbol ?: $row->product?->unit?->name, $row->quantity, $row->po_other]; }
}

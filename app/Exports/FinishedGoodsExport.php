<?php

namespace App\Exports;

use App\Domain\Manufacturing\Queries\FinishedGoodsTableQuery;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FinishedGoodsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private int $companyId, private ?string $from, private ?string $to) {}
    public function query() { return app(FinishedGoodsTableQuery::class)->builder($this->companyId, $this->from, $this->to); }
    public function headings(): array { return ['No Barang Jadi', 'Tanggal Barang Jadi', 'Style', 'Item Code', 'Item Name', 'Terima dari Packing', 'Hasil Barang Jadi', 'No MO', 'PO Buyer', 'User Input']; }
    public function map($row): array
    {
        $product = $row->productionOrder?->outputProduct;
        return [$row->finished_goods_number, $row->processed_at?->format('Y-m-d'), $product?->style_code, $product?->code, $product?->name, $row->input_quantity, $row->good_quantity, $row->productionOrder?->number, $row->productionOrder?->buyer_po_number, $row->processor?->username ?: $row->processor?->name];
    }
}

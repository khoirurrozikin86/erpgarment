<?php

namespace App\Exports;

use App\Domain\Purchasing\Queries\GoodsReceiptTableQuery;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GoodsReceiptsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    private int $rowNumber = 0;

    public function __construct(private readonly ?string $fromDate = null, private readonly ?string $toDate = null) {}

    public function query(): Builder
    {
        return (new GoodsReceiptTableQuery())->itemRowsBuilder($this->fromDate, $this->toDate);
    }

    public function headings(): array
    {
        return [
            'No.', 'No. GRN', 'Tanggal GRN', 'No. Invoice', 'Tanggal Invoice', 'Supplier', 'Style',
            'No. Material Request', 'No. PO Supplier', 'Status GRN', 'Kode Item', 'Nama Item', 'Satuan', 'Qty', 'Harga', 'Mata Uang', 'Total', 'Keterangan',
        ];
    }

    public function map($item): array
    {
        $receipt = $item->goodsReceipt;
        $style = $receipt?->purchaseOrder?->purchaseRequest?->materialRequest?->productionOrder?->outputProduct?->style_code
            ?? $receipt?->purchaseOrder?->purchaseRequest?->materialRequest?->outputProduct?->style_code
            ?? $item->product?->style_code;

        return [
            ++$this->rowNumber,
            $receipt?->number,
            $receipt?->received_at?->format('Y-m-d'),
            $receipt?->invoice_number,
            $receipt?->invoice_date?->format('Y-m-d'),
            $receipt?->supplier?->name,
            $style,
            $receipt?->purchaseOrder?->purchaseRequest?->materialRequest?->number,
            $receipt?->purchaseOrder?->number,
            $receipt?->status,
            $item->product?->code,
            $item->product?->name,
            $item->product?->unit?->symbol ?: $item->product?->unit?->name,
            (float) $item->quantity,
            (float) $item->unit_price,
            $receipt?->currency,
            (float) $item->line_total,
            $receipt?->notes,
        ];
    }
}

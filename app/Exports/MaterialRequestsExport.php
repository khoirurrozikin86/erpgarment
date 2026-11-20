<?php

namespace App\Exports;

use App\Domain\Purchasing\Queries\MaterialRequestTableQuery;
use App\Domain\Purchasing\Services\ProductionSourceSummary;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MaterialRequestsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly ?string $fromDate = null,
        private readonly ?string $toDate = null,
    ) {}

    public function query(): Builder
    {
        return (new MaterialRequestTableQuery())->builder($this->fromDate, $this->toDate);
    }

    public function headings(): array
    {
        return [
            'No.', 'No. Material Request', 'Tanggal Request', 'PO Buyer', 'Style',
            'Warna Produksi', 'Size Produksi', 'Item Code Produksi', 'Qty Produksi', 'Requester', 'Department', 'Tanggal Dibutuhkan',
            'Kode Item Dibutuhkan', 'Nama Item Dibutuhkan', 'Qty Item Dibutuhkan', 'Satuan Item',
            'Jumlah Item Material', 'Total Qty Material', 'Status Approval', 'Status Purchasing', 'Alasan',
        ];
    }

    public function map($materialRequest): array
    {
        $order = $materialRequest->productionOrder;
        $product = $order?->outputProduct ?? $materialRequest->outputProduct;
        $productionLines = (new ProductionSourceSummary())->lines($materialRequest);
        $styles = $productionLines->map(fn ($line) => $line['style'] ?: '')->implode("\n");
        $colors = $productionLines->map(fn ($line) => $line['color'] ?: '')->implode("\n");
        $sizes = $productionLines->map(fn ($line) => $line['size'] ?: '')->implode("\n");
        $quantities = $productionLines->map(fn ($line) => $line['quantity'] === null
            ? ''
            : rtrim(rtrim(number_format((float) $line['quantity'], 4, '.', ''), '0'), '.'))->implode("\n");

        $requestColumns = [
            $materialRequest->number,
            $materialRequest->created_at?->format('Y-m-d H:i:s'),
            $order?->buyer_po_number,
            $styles ?: $product?->style_code,
            $colors ?: null,
            $sizes ?: null,
            $product?->code,
            $quantities ?: ($order?->planned_quantity !== null
                ? (float) $order->planned_quantity
                : ($materialRequest->production_quantity !== null ? (float) $materialRequest->production_quantity : null)),
            $materialRequest->requester?->name,
            $materialRequest->department,
            $materialRequest->needed_at?->format('Y-m-d'),
        ];

        $items = $materialRequest->items;
        if ($items->isEmpty()) {
            return [[
                ++$this->rowNumber, ...$requestColumns, null, null, null, null,
                (int) $materialRequest->items_count, (float) ($materialRequest->items_total_quantity ?? 0),
                $materialRequest->status, $materialRequest->purchase_status, $materialRequest->reason,
            ]];
        }

        return $items->map(fn ($item) => [
            ++$this->rowNumber,
            ...$requestColumns,
            $item->product?->code,
            $item->product?->name,
            (float) $item->quantity,
            $item->product?->unit?->symbol ?: $item->product?->unit?->name,
            (int) $materialRequest->items_count,
            (float) ($materialRequest->items_total_quantity ?? 0),
            $materialRequest->status,
            $materialRequest->purchase_status,
            $materialRequest->reason,
        ])->all();
    }
}

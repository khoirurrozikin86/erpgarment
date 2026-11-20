<?php

namespace App\Exports;

use App\Domain\Purchasing\Queries\PurchaseOrderTableQuery;
use App\Domain\Purchasing\Services\ProductionSourceSummary;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseOrdersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private readonly ?string $fromDate = null,
        private readonly ?string $toDate = null,
    ) {}

    public function query(): Builder
    {
        return (new PurchaseOrderTableQuery())->builder($this->fromDate, $this->toDate);
    }

    public function headings(): array
    {
        return [
            'No. PO', 'No. PR', 'Supplier', 'Tanggal PO', 'Estimasi Tiba', 'Style',
            'Warna Produksi', 'Size Produksi', 'Qty Produksi', 'Kode Item Diminta', 'Nama Item Diminta', 'Color', 'Size', 'Qty Item Diminta', 'Satuan', 'Total', 'Status',
        ];
    }

    public function map($order): array
    {
        $materialRequest = $order->purchaseRequest?->materialRequest;
        $lines = (new ProductionSourceSummary())->lines($materialRequest);
        $styles = $lines->pluck('style')->filter()->implode("\n");
        $quantities = $lines->map(fn ($line) => $line['quantity'] === null
            ? ''
            : rtrim(rtrim(number_format((float) $line['quantity'], 4, '.', ''), '0'), '.'))->implode("\n");
        $colors = $lines->map(fn ($line) => $line['color'] ?: '')->implode("\n");
        $sizes = $lines->map(fn ($line) => $line['size'] ?: '')->implode("\n");
        $productionOrder = $materialRequest?->productionOrder;
        $product = $lines->first() ? null : ($productionOrder?->outputProduct ?? $materialRequest?->outputProduct);
        $orderColumns = [
            $order->number,
            $order->purchaseRequest?->number,
            $order->supplier?->name,
            $order->order_date?->format('Y-m-d'),
            $order->expected_delivery_at?->format('Y-m-d'),
            $styles ?: ($product?->style_code),
            $colors ?: null,
            $sizes ?: null,
            $quantities ?: ($productionOrder?->planned_quantity !== null
                ? (float) $productionOrder->planned_quantity
                : ($materialRequest?->production_quantity !== null ? (float) $materialRequest->production_quantity : null)),
        ];

        if ($order->items->isEmpty()) {
            return [[...$orderColumns, null, null, null, null, null, null, (float) $order->total_amount, $order->status]];
        }

        return $order->items->map(fn ($item) => [
            ...$orderColumns,
            $item->product?->code,
            $item->product?->name,
            $item->purchaseRequestItem?->materialRequestItem?->color ?: $item->product?->color,
            $item->purchaseRequestItem?->materialRequestItem?->size ?: $item->product?->size,
            (float) $item->quantity,
            $item->product?->unit?->symbol ?: $item->product?->unit?->name,
            (float) $order->total_amount,
            $order->status,
        ])->all();
    }
}

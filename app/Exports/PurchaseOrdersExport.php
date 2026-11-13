<?php

namespace App\Exports;

use App\Domain\Purchasing\Queries\PurchaseOrderTableQuery;
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
            'Qty Produksi', 'Kode Item Diminta', 'Nama Item Diminta', 'Qty Item Diminta', 'Satuan', 'Total', 'Status',
        ];
    }

    public function map($order): array
    {
        $materialRequest = $order->purchaseRequest?->materialRequest;
        $productionOrder = $materialRequest?->productionOrder;
        $product = $productionOrder?->outputProduct ?? $materialRequest?->outputProduct;
        $orderColumns = [
            $order->number,
            $order->purchaseRequest?->number,
            $order->supplier?->name,
            $order->order_date?->format('Y-m-d'),
            $order->expected_delivery_at?->format('Y-m-d'),
            $product?->style_code,
            $productionOrder?->planned_quantity !== null
                ? (float) $productionOrder->planned_quantity
                : ($materialRequest?->production_quantity !== null ? (float) $materialRequest->production_quantity : null),
        ];

        if ($order->items->isEmpty()) {
            return [[...$orderColumns, null, null, null, null, (float) $order->total_amount, $order->status]];
        }

        return $order->items->map(fn ($item) => [
            ...$orderColumns,
            $item->product?->code,
            $item->product?->name,
            (float) $item->quantity,
            $item->product?->unit?->symbol ?: $item->product?->unit?->name,
            (float) $order->total_amount,
            $order->status,
        ])->all();
    }
}

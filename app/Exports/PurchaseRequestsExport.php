<?php

namespace App\Exports;

use App\Domain\Purchasing\Queries\PurchaseRequestTableQuery;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseRequestsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private readonly ?string $fromDate = null,
        private readonly ?string $toDate = null,
    ) {}

    public function query(): Builder
    {
        return (new PurchaseRequestTableQuery())->builder($this->fromDate, $this->toDate);
    }

    public function headings(): array
    {
        return [
            'No. PR', 'Tanggal', 'No. MR', 'PO Buyer', 'Style', 'Item Code Produksi',
            'Qty Produksi', 'Kode Item Diminta', 'Nama Item Diminta', 'Qty Item Diminta', 'Satuan Item',
            'Supplier', 'Requester', 'Total Estimasi', 'Status',
        ];
    }

    public function map($purchaseRequest): array
    {
        $materialRequest = $purchaseRequest->materialRequest;
        $order = $materialRequest?->productionOrder;
        $product = $order?->outputProduct ?? $materialRequest?->outputProduct;

        $requestColumns = [
            $purchaseRequest->number,
            $purchaseRequest->created_at?->format('Y-m-d H:i:s'),
            $materialRequest?->number,
            $order?->buyer_po_number,
            $product?->style_code,
            $product?->code,
            $order?->planned_quantity !== null
                ? (float) $order->planned_quantity
                : ($materialRequest?->production_quantity !== null ? (float) $materialRequest->production_quantity : null),
        ];

        $items = $purchaseRequest->items;
        if ($items->isEmpty()) {
            return [[...$requestColumns, null, null, null, null, $purchaseRequest->supplier?->name,
                $purchaseRequest->requester?->name, (float) $purchaseRequest->total_amount, $purchaseRequest->status]];
        }

        return $items->map(fn ($item) => [
            ...$requestColumns,
            $item->product?->code,
            $item->product?->name,
            (float) $item->quantity,
            $item->product?->unit?->symbol ?: $item->product?->unit?->name,
            $purchaseRequest->supplier?->name,
            $purchaseRequest->requester?->name,
            (float) $purchaseRequest->total_amount,
            $purchaseRequest->status,
        ])->all();
    }
}

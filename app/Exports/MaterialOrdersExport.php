<?php

namespace App\Exports;

use App\Models\ProductionOrder;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MaterialOrdersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private readonly int $companyId,
        private readonly ?string $fromDate = null,
        private readonly ?string $toDate = null,
    ) {}

    public function query(): Builder
    {
        return ProductionOrder::query()
            ->where('company_id', $this->companyId)
            ->when($this->fromDate, fn (Builder $query) => $query->whereDate('po_date', '>=', $this->fromDate))
            ->when($this->toDate, fn (Builder $query) => $query->whereDate('po_date', '<=', $this->toDate))
            ->with(['outputProduct.unit', 'buyer'])
            ->orderByDesc('po_date')->orderByDesc('id');
    }

    public function headings(): array
    {
        return [
            'No. MO', 'Tanggal PO', 'Tanggal Tempo', 'PO Buyer', 'Buyer', 'No. CBD', 'Currency',
            'Destination', 'Item Code', 'Item Name', 'Unit', 'Style', 'Qty', 'Price Per Unit', 'Total Price', 'Description',
        ];
    }

    public function map($order): array
    {
        $product = $order->outputProduct;

        return [
            $order->number,
            $order->po_date?->format('Y-m-d'),
            $order->due_date?->format('Y-m-d'),
            $order->buyer_po_number,
            $order->buyer?->name,
            $order->no_cbd,
            $order->currency,
            $order->destination,
            $product?->code,
            $product?->name,
            $product?->unit?->symbol ?: $product?->unit?->name,
            $product?->style_code,
            (float) $order->planned_quantity,
            (float) $order->price_per_unit,
            (float) $order->total_price,
            $order->description,
        ];
    }
}

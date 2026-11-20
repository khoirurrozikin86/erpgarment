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
            ->with(['outputProduct.unit', 'outputProduct.parentProduct', 'buyer', 'billOfMaterial', 'materials.billOfMaterial'])
            ->orderByDesc('po_date')->orderByDesc('id');
    }

    public function headings(): array
    {
        return [
            'No. Production Order', 'Tanggal Production Order', 'Tanggal Tempo', 'PO Buyer', 'Buyer', 'No. CBD', 'Currency',
            'Destination', 'Item Code', 'Item Name', 'Color', 'Size', 'Unit', 'Style', 'BOM Number', 'BOM Type', 'Qty', 'Price Per Unit', 'Total Price', 'Description',
        ];
    }

    public function map($order): array
    {
        $product = $order->outputProduct;
        $boms = $order->materials->pluck('billOfMaterial')->merge([$order->billOfMaterial])->filter()->unique('id');

        return [
            $order->number,
            $order->po_date?->format('Y-m-d'),
            $order->due_date?->format('Y-m-d'),
            $order->buyer_po_number,
            $order->buyer?->name,
            $order->no_cbd,
            $order->currency,
            $order->destination,
            $product?->parentProduct?->code ?: $product?->code,
            $product?->name,
            $order->line_color,
            $order->line_size,
            $product?->unit?->symbol ?: $product?->unit?->name,
            $order->model_style,
            $boms->pluck('number')->implode(', '),
            $boms->map(fn ($bom) => __('variants.bom_type_'.$bom->bom_type))->unique()->implode(', '),
            (float) $order->planned_quantity,
            (float) $order->price_per_unit,
            (float) $order->total_price,
            $order->description,
        ];
    }
}

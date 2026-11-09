<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\StockCardQuery;
use App\Models\StockMovement;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockCardExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private int $productId,
        private ?string $fromDate,
        private ?string $toDate,
    ) {}

    public function query()
    {
        return app(StockCardQuery::class)->movements($this->productId, $this->fromDate, $this->toDate);
    }

    public function headings(): array
    {
        return [
            'Tanggal Pencatatan',
            'Kode Barang',
            'Nama Barang',
            'No. Dokumen',
            'Tipe Pergerakan',
            'Stok Masuk',
            'Stok Keluar',
            'Saldo',
            'Satuan',
            'Catatan',
        ];
    }

    public function map($movement): array
    {
        /** @var StockMovement $movement */
        return [
            $movement->created_at?->format('Y-m-d H:i:s'),
            $movement->product?->code,
            $movement->product?->name,
            $movement->goodsReceipt?->number ?? $movement->purchaseReturn?->number ?? $movement->customerReturn?->number ?? $movement->stockOpname?->number ?? $movement->delivery?->number ?? $movement->posSale?->number,
            match ($movement->movement_type) {
                'purchase_receipt' => 'Penerimaan Barang',
                'purchase_return' => 'Purchase Return',
                'customer_return' => 'Customer Return',
                'pos_void' => 'Void POS',
                'stock_adjustment' => 'Stock Opname',
                'sales_delivery' => 'Delivery',
                'pos_sale' => 'Penjualan POS',
                default => ucfirst(str_replace('_', ' ', $movement->movement_type)),
            },
            (float) $movement->quantity > 0 ? (float) $movement->quantity : 0,
            (float) $movement->quantity < 0 ? abs((float) $movement->quantity) : 0,
            (float) $movement->quantity_after,
            $movement->product?->unit?->symbol ?: $movement->product?->unit?->name,
            $movement->notes,
        ];
    }
}

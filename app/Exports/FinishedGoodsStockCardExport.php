<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\FinishedGoodsStockCardQuery;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FinishedGoodsStockCardExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private ?Collection $products = null,
        private ?Product $product = null,
        private ?Collection $movements = null,
    ) {}

    public function collection(): Collection
    {
        return $this->products ?? $this->movements ?? collect();
    }

    public function headings(): array
    {
        if ($this->products) return [__('variants.stock_parent_code'), __('variants.stock_sku'), 'Nama Barang', 'Warna / Color', 'Ukuran / Size', 'Satuan'];

        return [__('variants.stock_parent_code'), __('variants.stock_sku'), 'Warna / Color', 'Ukuran / Size', 'Satuan', 'Tanggal', 'No Mutasi', 'Uraian', 'Jumlah', '(+/-)', 'Saldo', 'Pengguna'];
    }

    public function map($row): array
    {
        if ($row instanceof Product) {
            return [$row->parentProduct?->code ?? $row->code, $row->parent_product_id ? $row->code : null, $row->name, $row->color, $row->size, $row->unit?->symbol ?: $row->unit?->name ?: '—'];
        }

        /** @var StockMovement $row */
        $query = app(FinishedGoodsStockCardQuery::class);

        return [
            $this->product?->parentProduct?->code ?? $this->product?->code,
            $this->product?->parent_product_id ? $this->product->code : null,
            $this->product?->color, $this->product?->size,
            $this->product?->unit?->symbol ?: $this->product?->unit?->name,
            $row->created_at?->format('Y-m-d'),
            $query->documentNumber($row),
            $query->description($row),
            abs((float) $row->quantity),
            (float) $row->quantity > 0 ? '+' : ((float) $row->quantity < 0 ? '-' : 'S'),
            (float) $row->quantity_after,
            $row->createdBy?->name ?: '—',
        ];
    }
}

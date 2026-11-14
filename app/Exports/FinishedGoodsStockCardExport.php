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
        if ($this->products) return ['No MO', 'Kode Barang', 'Nama Barang', 'Satuan'];

        return ['Tanggal', 'No Mutasi', 'Uraian', 'Jumlah', '(+/-)', 'Saldo', 'Pengguna'];
    }

    public function map($row): array
    {
        if ($row instanceof Product) {
            return ['', $row->code, $row->name, $row->unit?->symbol ?: $row->unit?->name ?: '—'];
        }

        /** @var StockMovement $row */
        $query = app(FinishedGoodsStockCardQuery::class);

        return [
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

<?php

namespace App\Exports;

use App\Models\BillOfMaterialItem;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BillOfMaterialsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    private int $rowNumber = 0;

    public function __construct(private int $companyId) {}

    public function query(): Builder
    {
        return BillOfMaterialItem::query()
            ->with([
                'billOfMaterial.product.unit',
                'billOfMaterial.creator',
                'product.unit',
            ])
            ->whereHas('billOfMaterial', function (Builder $query) {
                $query->where('company_id', $this->companyId)
                    ->whereHas('product', function (Builder $product) {
                        $product->whereNotNull('style_code')
                            ->whereHas('category', fn (Builder $category) => $category
                                ->where('code', 'FG')
                                ->orWhereRaw('LOWER(name) = ?', ['finished goods']));
                    });
            })
            ->orderBy('bill_of_material_id')
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'NO', 'STYLE', 'KODE PRODUK', 'NAMA PRODUK', 'SATUAN PRODUK',
            'KODE BAHAN', 'NAMA BAHAN', 'SATUAN BAHAN', 'KONSUMSI',
            'SPAN', 'SCRAP', 'USER INPUT',
        ];
    }

    public function map($item): array
    {
        $bom = $item->billOfMaterial;
        $product = $bom?->product;
        $material = $item->product;

        return [
            ++$this->rowNumber,
            $product?->style_code,
            $product?->code,
            $product?->name,
            $product?->unit?->symbol ?: $product?->unit?->name,
            $material?->code,
            $material?->name,
            $material?->unit?->symbol ?: $material?->unit?->name,
            (float) $item->quantity,
            (float) $item->span,
            (float) $item->wastage_percent,
            $bom?->creator?->name,
        ];
    }
}

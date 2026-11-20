<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\StockBalanceQuery;
use App\Domain\Inventory\Queries\StockMovementDimensions;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockBalancesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return app(StockBalanceQuery::class)->builder();
    }

    public function headings(): array
    {
        return [
            __('variants.stock_parent_code'), __('variants.stock_sku'), __('ui.common.item_name'),
            __('ui.common.category'), __('ui.workflow.color'), __('ui.workflow.size'), __('ui.common.unit'),
            __('variants.stock_sku_balance'), __('variants.global_stock'), __('ui.workflow.reorder_point'), __('ui.common.status'),
        ];
    }

    public function map($product): array
    {
        /** @var Product $product */
        $dimensions = $product->parent_product_id
            ? StockMovementDimensions::summarize($product->stockMovements)
            : ['color' => '—', 'size' => '—'];
        $quantity = (float) ($product->stock?->quantity ?? 0);
        $reorderPoint = (float) $product->reorder_point;
        $threshold = $reorderPoint > 0 ? $reorderPoint : (float) $product->min_stock;
        $status = $quantity <= 0 && $threshold > 0
            ? 'Habis'
            : ($threshold > 0 && $quantity <= $threshold ? 'Stok Rendah' : 'Aman');

        return [
            $product->parentProduct?->code ?? $product->code,
            $product->code,
            $product->name,
            $product->category?->name,
            $dimensions['color'],
            $dimensions['size'],
            $product->unit?->symbol ?: $product->unit?->name,
            $quantity,
            (float) ($product->global_quantity ?? 0),
            $threshold,
            $status,
        ];
    }
}

<?php

namespace App\Domain\Inventory\Queries;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;

class FinishedGoodsStockCardQuery
{
    public function products(): Builder
    {
        return Product::query()
            ->where('track_stock', true)
            ->whereHas('category', fn (Builder $query) => $query
                ->where('code', 'FG')
                ->orWhereRaw('LOWER(name) = ?', ['finished goods']))
            ->with('unit:id,name,symbol')
            ->select(['id', 'code', 'name', 'unit_id', 'track_stock'])
            ->orderBy('code');
    }

    public function movements(int $productId): Builder
    {
        return StockMovement::query()
            ->where('product_id', $productId)
            ->with([
                'goodsSentNote:id,number',
                'productionOrderStageEntry' => fn ($query) => $query->withTrashed()
                    ->select('id', 'production_order_id', 'finished_goods_number', 'packing_number'),
                'productionOrderStageEntry.productionOrder:id,number',
                'stockOpname:id,number',
                'createdBy:id,name',
            ])
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function documentNumber(StockMovement $movement): string
    {
        return $movement->goodsSentNote?->number
            ?? $movement->productionOrderStageEntry?->finished_goods_number
            ?? $movement->productionOrderStageEntry?->packing_number
            ?? $movement->stockOpname?->number
            ?? $movement->productionOrderStageEntry?->productionOrder?->number
            ?? '—';
    }

    public function description(StockMovement $movement): string
    {
        return match ($movement->movement_type) {
            'production_output' => 'Hasil Produksi',
            'production_output_reversal' => 'Koreksi Hasil Produksi',
            'goods_sent_note' => 'Pengiriman Barang Jadi',
            'goods_sent_note_reversal' => 'Koreksi Pengiriman Barang Jadi',
            'stock_adjustment' => 'Saldo Fisik / Stock Opname',
            'production_return' => 'Penerimaan Retur Produksi',
            'production_return_reversal' => 'Koreksi Retur Produksi',
            default => ucfirst(str_replace('_', ' ', $movement->movement_type)),
        };
    }
}

<?php

namespace App\Domain\Inventory\Queries;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;

class ScrapStockCardQuery
{
    public function products(): Builder
    {
        return Product::query()
            ->where('track_stock', true)
            ->where('is_active', true)
            ->whereHas('category', fn (Builder $query) => $query->where('code', 'WASTE'))
            ->with(['category:id,code,name', 'unit:id,name,symbol'])
            ->select(['id', 'code', 'name', 'category_id', 'unit_id', 'track_stock'])
            ->orderBy('code');
    }

    public function movements(int $productId): Builder
    {
        return app(StockCardQuery::class)->movements($productId, null, null);
    }

    public function documentNumber(StockMovement $movement): string
    {
        return $movement->scrapReceiptDocument?->number
            ?? $movement->scrapIssueDocument?->number
            ?? $movement->stockOpname?->number
            ?? '—';
    }

    public function description(StockMovement $movement): string
    {
        return match ($movement->movement_type) {
            'scrap_receipt' => __('ui.scrap_receipt.title'),
            'scrap_receipt_reversal' => __('ui.scrap_stock_card.receipt_reversal'),
            'scrap_issue' => __('ui.scrap_issue.title'),
            'scrap_issue_reversal' => __('ui.scrap_stock_card.issue_reversal'),
            'stock_adjustment' => __('ui.scrap_stock_card.stock_adjustment'),
            default => ucfirst(str_replace('_', ' ', $movement->movement_type)),
        };
    }

    public function transactionDate(StockMovement $movement): string
    {
        $date = $movement->scrapReceiptDocument?->receipt_date
            ?? $movement->scrapIssueDocument?->issue_date
            ?? $movement->created_at;

        return $date?->format('Y-m-d') ?? '—';
    }
}

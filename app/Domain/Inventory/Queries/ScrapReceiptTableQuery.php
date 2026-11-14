<?php

namespace App\Domain\Inventory\Queries;

use App\Models\Product;
use App\Models\ScrapReceiptDocument;
use Illuminate\Database\Eloquent\Builder;

class ScrapReceiptTableQuery
{
    public function builder(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return ScrapReceiptDocument::query()->where('company_id', $companyId)
            ->when($from, fn (Builder $q) => $q->whereDate('receipt_date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('receipt_date', '<=', $to))
            ->with(['scrapProduct:id,code,name,unit_id', 'scrapProduct.unit:id,name,symbol', 'sourceProduct:id,code,name,unit_id', 'sourceProduct.unit:id,name,symbol', 'creator:id,name'])
            ->select('scrap_receipt_documents.*')->latest('receipt_date')->latest('id');
    }

    public function scrapProducts(): Builder
    {
        return Product::query()->where('track_stock', true)->where('is_active', true)
            ->whereHas('category', fn (Builder $category) => $category->where('code', 'WASTE'))
            ->with('unit:id,name,symbol')->select(['id', 'code', 'name', 'unit_id'])->orderBy('code');
    }

    public function sourceProducts(): Builder
    {
        return Product::query()->where('track_stock', true)->where('is_active', true)
            ->with('unit:id,name,symbol')->select(['id', 'code', 'name', 'unit_id'])->orderBy('code');
    }
}

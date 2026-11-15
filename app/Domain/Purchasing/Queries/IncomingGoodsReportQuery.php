<?php

namespace App\Domain\Purchasing\Queries;

use App\Models\GoodsReceiptItem;
use Illuminate\Database\Eloquent\Builder;

class IncomingGoodsReportQuery
{
    public function builder(array $filters): Builder
    {
        return GoodsReceiptItem::query()->with(['goodsReceipt.supplier', 'product.unit'])
            ->whereHas('goodsReceipt', function (Builder $query) use ($filters) {
                $query->where('status', 'approved')
                    ->where('customs_document_date', '>=', $filters['from_date'])
                    ->where('customs_document_date', '<=', $filters['to_date']);
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->whereHas('product', fn (Builder $q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                        ->orWhereHas('goodsReceipt', fn (Builder $q) => $q->where('number', 'like', "%{$search}%")->orWhere('customs_document_number', 'like', "%{$search}%"))
                        ->orWhereHas('goodsReceipt.supplier', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
                });
            })->orderByDesc('goods_receipt_id')->orderBy('id');
    }
}

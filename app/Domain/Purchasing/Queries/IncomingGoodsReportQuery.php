<?php

namespace App\Domain\Purchasing\Queries;

use App\Models\GoodsReceiptItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class IncomingGoodsReportQuery
{
    public function builder(array $filters): Builder
    {
        $from = $filters['from_date'];
        $until = CarbonImmutable::parse($filters['to_date'])->addDay()->toDateString();

        return GoodsReceiptItem::query()->with([
            'goodsReceipt.supplier',
            'product.unit',
            'product.parentProduct:id,code',
            'purchaseOrderItem.purchaseRequestItem.materialRequestItem:id,color,size',
        ])
            ->whereHas('goodsReceipt', function (Builder $query) use ($from, $until) {
                $query->where('status', 'approved')
                    ->where(fn (Builder $dates) => $dates
                        ->where(fn (Builder $bc) => $bc->where('customs_document_date', '>=', $from)
                            ->where('customs_document_date', '<', $until))
                        ->orWhere(fn (Builder $receipt) => $receipt->whereNull('customs_document_date')
                            ->where('received_at', '>=', $from)->where('received_at', '<', $until)));
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->whereHas('product', fn (Builder $q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                        ->orWhereHas('product.parentProduct', fn (Builder $q) => $q->where('code', 'like', "%{$search}%"))
                        ->orWhereHas('goodsReceipt', fn (Builder $q) => $q->where('number', 'like', "%{$search}%")->orWhere('customs_document_number', 'like', "%{$search}%"))
                        ->orWhereHas('goodsReceipt.supplier', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
                });
            })->orderByDesc('goods_receipt_id')->orderBy('id');
    }
}

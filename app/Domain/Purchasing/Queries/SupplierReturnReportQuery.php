<?php

namespace App\Domain\Purchasing\Queries;

use App\Models\PurchaseReturnItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class SupplierReturnReportQuery
{
    public function builder(array $filters): Builder
    {
        $from = CarbonImmutable::parse($filters['from_date'])->startOfDay();
        $until = CarbonImmutable::parse($filters['to_date'])->addDay()->startOfDay();
        return PurchaseReturnItem::query()->with(['purchaseReturn', 'product.unit', 'product.parentProduct:id,code', 'goodsReceiptItem.product:id,color,size',
            'goodsReceiptItem.purchaseOrderItem.purchaseRequestItem.materialRequestItem'])
            ->whereHas('purchaseReturn', fn (Builder $q) => $q->where('returned_at', '>=', $from)->where('returned_at', '<', $until))
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $q) use ($search) {
                    $q->whereHas('purchaseReturn', fn (Builder $q) => $q->where('number', 'like', "%{$search}%"))
                        ->orWhereHas('product', fn (Builder $q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhereHas('parentProduct', fn (Builder $parent) => $parent->where('code', 'like', "%{$search}%")));
                });
            })->orderBy('purchase_return_id')->orderBy('id');
    }
}

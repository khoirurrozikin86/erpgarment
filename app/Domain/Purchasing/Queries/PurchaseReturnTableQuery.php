<?php

namespace App\Domain\Purchasing\Queries;

use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use App\Models\GoodsReceipt;
use App\Models\PurchaseReturn;
use Illuminate\Database\Eloquent\Builder;

class PurchaseReturnTableQuery
{
    use AppliesDateRange;

    public function builder(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = PurchaseReturn::query()
            ->with(['goodsReceipt:id,number', 'supplier:id,name', 'returner:id,name'])
            ->withCount('items')
            ->orderByDesc('returned_at');

        return $this->applyDateRange($query, 'returned_at', $fromDate, $toDate);
    }

    public function eligibleGoodsReceipts(): Builder
    {
        return GoodsReceipt::query()
            ->whereHas('items', fn (Builder $items) => $items->whereColumn('returned_quantity', '<', 'quantity'))
            ->with([
                'purchaseOrder:id,number',
                'supplier:id,name',
                'items.product:id,code,name,unit_id,track_stock',
                'items.product.unit:id,name,symbol',
            ])
            ->withCount('items')
            ->orderByDesc('received_at');
    }

    public function sourceGoodsReceipt(int $id): GoodsReceipt
    {
        return $this->eligibleGoodsReceipts()->findOrFail($id);
    }

    public function details(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        return PurchaseReturn::query()
            ->with([
                'goodsReceipt:id,number,purchase_order_id', 'goodsReceipt.purchaseOrder:id,number',
                'supplier', 'returner:id,name',
                'items.product:id,code,name,unit_id,track_stock', 'items.product.unit:id,name,symbol',
            ])
            ->findOrFail($purchaseReturn->id);
    }
}

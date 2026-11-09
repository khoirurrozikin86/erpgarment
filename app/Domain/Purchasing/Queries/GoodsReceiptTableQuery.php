<?php

namespace App\Domain\Purchasing\Queries;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use Illuminate\Database\Eloquent\Builder;

class GoodsReceiptTableQuery
{
    use AppliesDateRange;

    public function builder(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = GoodsReceipt::query()
            ->with(['purchaseOrder:id,number', 'supplier:id,name', 'receiver:id,name'])
            ->withCount('items')
            ->orderByDesc('received_at');

        return $this->applyDateRange($query, 'received_at', $fromDate, $toDate);
    }

    public function eligiblePurchaseOrders(): Builder
    {
        return PurchaseOrder::query()
            ->whereIn('status', ['issued', 'partially_received'])
            ->whereHas('items', fn (Builder $items) => $items->whereColumn('received_quantity', '<', 'quantity'))
            ->with(['supplier:id,name', 'items.product:id,code,name,unit_id,track_stock', 'items.product.unit:id,name,symbol'])
            ->withCount('items')
            ->orderByDesc('issued_at');
    }

    public function sourcePurchaseOrder(int $id): PurchaseOrder
    {
        return $this->eligiblePurchaseOrders()->findOrFail($id);
    }

    public function details(GoodsReceipt $receipt): GoodsReceipt
    {
        return GoodsReceipt::query()
            ->with([
                'purchaseOrder:id,number,status', 'supplier', 'receiver:id,name',
                'items.product:id,code,name,unit_id,track_stock', 'items.product.unit:id,name,symbol',
            ])
            ->findOrFail($receipt->id);
    }
}

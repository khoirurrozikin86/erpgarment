<?php

namespace App\Domain\Sales\Queries;

use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use App\Models\Delivery;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Builder;

class DeliveryTableQuery
{
    use AppliesDateRange;

    public function builder(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = Delivery::query()
            ->with(['salesOrder:id,number', 'customer:id,code,name', 'deliverer:id,name'])
            ->withCount('items')
            ->orderByDesc('delivered_at');
        return $this->applyDateRange($query, 'delivered_at', $fromDate, $toDate);
    }

    public function eligibleOrders(): Builder
    {
        return SalesOrder::query()
            ->whereIn('status', ['confirmed', 'partially_delivered'])
            ->whereHas('items', fn ($query) => $query->whereColumn('delivered_quantity', '<', 'quantity'))
            ->with(['customer:id,code,name', 'quotation:id,number'])
            ->withCount('items')
            ->orderByDesc('order_date');
    }

    public function sourceOrder(int $id): SalesOrder
    {
        return $this->eligibleOrders()
            ->with('items.product.unit', 'items.product.stock')
            ->findOrFail($id);
    }

    public function details(Delivery $delivery): Delivery
    {
        return Delivery::query()
            ->with(['salesOrder.quotation', 'customer', 'deliverer', 'items.product.unit'])
            ->findOrFail($delivery->id);
    }
}

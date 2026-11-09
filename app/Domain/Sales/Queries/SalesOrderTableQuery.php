<?php

namespace App\Domain\Sales\Queries;

use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use App\Models\SalesOrder;
use App\Models\SalesQuotation;
use Illuminate\Database\Eloquent\Builder;

class SalesOrderTableQuery
{
    use AppliesDateRange;

    public function builder(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = SalesOrder::query()
            ->with(['quotation:id,number', 'customer:id,code,name', 'creator:id,name', 'confirmer:id,name'])
            ->withCount('items')
            ->orderByDesc('order_date')->orderByDesc('id');
        return $this->applyDateRange($query, 'order_date', $fromDate, $toDate);
    }

    public function eligibleQuotations(): Builder
    {
        return SalesQuotation::query()
            ->where('status', 'accepted')
            ->whereDoesntHave('salesOrder')
            ->with(['customer:id,code,name'])
            ->withCount('items')
            ->orderByDesc('quote_date');
    }

    public function sourceQuotation(int $id): SalesQuotation
    {
        return $this->eligibleQuotations()->with('items.product.unit')->findOrFail($id);
    }

    public function details(SalesOrder $order): SalesOrder
    {
        return SalesOrder::query()->with(['quotation', 'customer', 'creator', 'confirmer', 'items.product.unit'])->findOrFail($order->id);
    }
}

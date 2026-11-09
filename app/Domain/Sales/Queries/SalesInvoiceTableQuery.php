<?php

namespace App\Domain\Sales\Queries;

use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use App\Models\Delivery;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Builder;

class SalesInvoiceTableQuery
{
    use AppliesDateRange;

    public function builder(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = SalesInvoice::query()
            ->with(['delivery:id,number', 'salesOrder:id,number', 'customer:id,code,name,email', 'creator:id,name', 'issuer:id,name'])
            ->withCount('items')->orderByDesc('invoice_date')->orderByDesc('id');
        return $this->applyDateRange($query, 'invoice_date', $fromDate, $toDate);
    }

    public function eligibleDeliveries(): Builder
    {
        return Delivery::query()->where('status', 'posted')->whereDoesntHave('salesInvoice')
            ->with(['salesOrder:id,number', 'customer:id,code,name,email'])->withCount('items')->orderByDesc('delivered_at');
    }

    public function sourceDelivery(int $id): Delivery
    {
        return $this->eligibleDeliveries()->with(['items.product.unit', 'items.salesOrderItem'])->findOrFail($id);
    }

    public function details(SalesInvoice $invoice): SalesInvoice
    {
        return SalesInvoice::query()->with([
            'delivery.salesOrder', 'salesOrder', 'customer', 'creator', 'issuer', 'items.product.unit', 'items.deliveryItem',
        ])->findOrFail($invoice->id);
    }
}

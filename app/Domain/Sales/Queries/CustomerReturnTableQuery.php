<?php

namespace App\Domain\Sales\Queries;

use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use App\Models\CustomerReturn;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Builder;

class CustomerReturnTableQuery
{
    use AppliesDateRange;

    public function builder(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = CustomerReturn::query()
            ->with(['invoice:id,number', 'customer:id,name', 'cashBankAccount:id,code,name', 'returner:id,name'])
            ->withCount('items')
            ->orderByDesc('returned_at');

        return $this->applyDateRange($query, 'returned_at', $fromDate, $toDate);
    }

    public function eligibleInvoices(): Builder
    {
        return SalesInvoice::query()
            ->where('status', 'issued')
            ->whereHas('items', fn(Builder $items) => $items->whereColumn('returned_quantity', '<', 'quantity'))
            ->with(['customer:id,code,name'])
            ->withCount('items')
            ->orderByDesc('invoice_date');
    }

    public function sourceInvoice(int $id): SalesInvoice
    {
        return $this->eligibleInvoices()
            ->with(['items.product:id,code,name,unit_id,track_stock', 'items.product.unit:id,name,symbol'])
            ->findOrFail($id);
    }

    public function details(CustomerReturn $customerReturn): CustomerReturn
    {
        return CustomerReturn::query()
            ->with([
                'invoice:id,number,invoice_date',
                'customer:id,code,name',
                'cashBankAccount:id,code,name',
                'returner:id,name',
                'items.product:id,code,name,unit_id',
                'items.product.unit:id,name,symbol',
            ])
            ->findOrFail($customerReturn->id);
    }
}

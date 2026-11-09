<?php

namespace App\Domain\Accounting\Queries;

use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Builder;

class AccountsReceivableQuery
{
    use AppliesDateRange;

    public function invoices(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = SalesInvoice::query()
            ->where('status', 'issued')
            ->with(['customer:id,code,name', 'delivery:id,number'])
            ->withSum('payments as paid_amount', 'amount')
            ->orderByDesc('invoice_date')->orderByDesc('id');
        return $this->applyDateRange($query, 'invoice_date', $fromDate, $toDate);
    }
}

<?php

namespace App\Domain\Sales\Queries;

use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Builder;

class SalesReportQuery
{
    use AppliesDateRange;

    public function builder(?string $fromDate, ?string $toDate): Builder
    {
        $query = SalesInvoice::query()->where('status', 'issued')
            ->with('customer:id,name,code')
            ->withSum('payments as paid_total', 'amount')
            ->orderByDesc('invoice_date')->orderByDesc('id');

        return $this->applyDateRange($query, 'invoice_date', $fromDate, $toDate);
    }
}

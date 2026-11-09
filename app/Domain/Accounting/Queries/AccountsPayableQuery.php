<?php

namespace App\Domain\Accounting\Queries;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use App\Models\GoodsReceipt;
use Illuminate\Database\Eloquent\Builder;

class AccountsPayableQuery
{
    use AppliesDateRange;

    public function __construct(private CompanyContext $company) {}

    public function receipts(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = GoodsReceipt::query()->with(['supplier:id,code,name', 'purchaseOrder:id,number'])
            ->withSum('items as receipt_amount', 'line_total')
            ->withSum('purchaseReturns as returned_amount', 'total_amount')
            ->withSum(['supplierPayments as paid_amount' => fn (Builder $payments) => $payments->where('company_id', $this->company->id())], 'amount')
            ->orderByDesc('received_at')->orderByDesc('id');
        return $this->applyDateRange($query, 'received_at', $fromDate, $toDate);
    }

    public function find(int $id): GoodsReceipt
    {
        return GoodsReceipt::query()->with(['supplier:id,code,name', 'supplierPayments' => fn (Builder $payments) => $payments
            ->where('company_id', $this->company->id())->with(['payer:id,name', 'cashBankAccount:id,code,name'])->orderByDesc('payment_date')])
            ->findOrFail($id);
    }
}

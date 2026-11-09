<?php

namespace App\Domain\Purchasing\Queries;

use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use Illuminate\Database\Eloquent\Builder;

class PurchaseReportQuery
{
    use AppliesDateRange;

    public function receipts(?string $fromDate, ?string $toDate): Builder
    {
        $query = GoodsReceipt::query()->with(['purchaseOrder:id,number', 'supplier:id,name,code'])
            ->withSum('items as received_value', 'line_total')
            ->orderByDesc('received_at')->orderByDesc('id');
        return $this->applyDateRange($query, 'received_at', $fromDate, $toDate);
    }

    public function orders(?string $fromDate, ?string $toDate): Builder
    {
        $query = PurchaseOrder::query()->whereIn('status', ['issued', 'partially_received', 'received'])
            ->with('supplier:id,name,code')->orderByDesc('order_date')->orderByDesc('id');
        return $this->applyDateRange($query, 'order_date', $fromDate, $toDate);
    }

    public function returns(?string $fromDate, ?string $toDate): Builder
    {
        $query = PurchaseReturn::query()->with(['goodsReceipt:id,number,purchase_order_id', 'goodsReceipt.purchaseOrder:id,number', 'supplier:id,name,code'])
            ->orderByDesc('returned_at')->orderByDesc('id');
        return $this->applyDateRange($query, 'returned_at', $fromDate, $toDate);
    }
}

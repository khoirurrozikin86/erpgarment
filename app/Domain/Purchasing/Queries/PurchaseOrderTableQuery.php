<?php

namespace App\Domain\Purchasing\Queries;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderTableQuery
{
    use AppliesDateRange;

    public function builder(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = PurchaseOrder::query()
            ->with([
                'purchaseRequest:id,number,material_request_id',
                'purchaseRequest.materialRequest:id,production_order_id,output_product_id,production_quantity',
                'purchaseRequest.materialRequest.productionOrder:id,output_product_id,planned_quantity',
                'purchaseRequest.materialRequest.productionOrder.outputProduct:id,code,name,style_code',
                'purchaseRequest.materialRequest.outputProduct:id,code,name,style_code',
                'supplier:id,name,email',
                'creator:id,name',
                'items:id,purchase_order_id,product_id,quantity',
                'items.product:id,code,name,unit_id',
                'items.product.unit:id,name,symbol',
            ])
            ->select([
                'id', 'number', 'purchase_request_id', 'supplier_id', 'created_by', 'order_date',
                'expected_delivery_at', 'total_amount', 'status', 'issued_at', 'created_at', 'updated_at',
            ])
            ->withCount('items')
            ->orderByDesc('created_at');

        return $this->applyDateRange($query, 'order_date', $fromDate, $toDate);
    }

    public function eligiblePurchaseRequests(): Builder
    {
        return PurchaseRequest::query()
            ->where('status', 'approved')
            ->whereDoesntHave('purchaseOrder')
            ->with(['supplier:id,name', 'requester:id,name'])
            ->withCount('items')
            ->orderByDesc('created_at');
    }

    public function sourcePurchaseRequest(int $id): PurchaseRequest
    {
        return $this->eligiblePurchaseRequests()
            ->with('items.product.unit')
            ->findOrFail($id);
    }

    public function details(PurchaseOrder $order): PurchaseOrder
    {
        return PurchaseOrder::query()
            ->with(['purchaseRequest', 'supplier', 'creator', 'items.product.unit'])
            ->findOrFail($order->id);
    }
}

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
                'purchaseRequest.materialRequest:id,production_order_id,production_order_header_id,source_details,output_product_id,production_quantity',
                'purchaseRequest.materialRequest.productionOrderHeader.details.outputProduct:id,code,name,style_code,color,size',
                'purchaseRequest.materialRequest.productionOrder:id,output_product_id,planned_quantity',
                'purchaseRequest.materialRequest.productionOrder.outputProduct:id,code,name,style_code,color,size',
                'purchaseRequest.materialRequest.outputProduct:id,code,name,style_code,color,size',
                'supplier:id,name,email',
                'creator:id,name',
                'items:id,purchase_order_id,purchase_request_item_id,product_id,quantity',
                'items.purchaseRequestItem:id,material_request_item_id,product_id',
                'items.purchaseRequestItem.materialRequestItem:id,product_id,color,size',
                'items.product:id,code,name,unit_id,color,size',
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
            ->with([
                'items.product.unit',
                'items.materialRequestItem',
                'materialRequest.productionOrder.outputProduct',
                'materialRequest.productionOrderHeader.details.outputProduct',
                'materialRequest.outputProduct',
            ])
            ->findOrFail($id);
    }

    public function details(PurchaseOrder $order): PurchaseOrder
    {
        return PurchaseOrder::query()
            ->with([
                'purchaseRequest', 'supplier', 'creator', 'items.product.unit',
                'items.purchaseRequestItem.materialRequestItem',
            ])
            ->findOrFail($order->id);
    }
}

<?php

namespace App\Domain\Purchasing\Queries;

use App\Models\MaterialRequest;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PurchaseRequestTableQuery
{
    use AppliesDateRange;

    public function builder(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = PurchaseRequest::query()
            ->with([
                'materialRequest:id,number,production_order_id,production_order_header_id,source_details,output_product_id,production_quantity',
                'materialRequest.productionOrder:id,buyer_po_number,output_product_id,planned_quantity',
                'materialRequest.productionOrderHeader.details.outputProduct:id,code,name,style_code,color,size',
                'materialRequest.productionOrder.outputProduct:id,code,name,style_code,color,size',
                'materialRequest.outputProduct:id,code,name,style_code,color,size',
                'supplier:id,name',
                'requester:id,name',
                'reviewer:id,name',
                'purchaseOrder:id,purchase_request_id,number,status',
                'items:id,purchase_request_id,material_request_item_id,product_id,quantity',
                'items.materialRequestItem:id,product_id,color,size',
                'items.product:id,code,name,unit_id,color,size',
                'items.product.unit:id,name,symbol',
            ])
            ->select([
                'id', 'number', 'material_request_id', 'supplier_id', 'requested_by',
                'total_amount', 'notes', 'status', 'reviewed_by', 'reviewed_at', 'review_note',
                'created_at', 'updated_at',
            ])
            ->withCount('items')
            ->orderByDesc('created_at');

        return $this->applyDateRange($query, 'created_at', $fromDate, $toDate);
    }

    public function eligibleMaterialRequests(): Builder
    {
        return MaterialRequest::query()
            ->where('status', 'approved')
            ->where('purchase_status', '!=', 'processed')
            ->orderByDesc('created_at');
    }

    public function sourceMaterialRequest(int $id): MaterialRequest
    {
        return $this->eligibleMaterialRequests()
            ->with([
                'items.product.unit',
                'productionOrder.outputProduct',
                'productionOrderHeader.details.outputProduct',
                'outputProduct',
            ])
            ->findOrFail($id);
    }

    public function activeSuppliers(): Collection
    {
        return Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
    }
}

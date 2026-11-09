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
            ->with(['materialRequest:id,number', 'supplier:id,name', 'requester:id,name', 'reviewer:id,name', 'purchaseOrder:id,purchase_request_id,number,status'])
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
            ->with(['items.product.unit'])
            ->findOrFail($id);
    }

    public function activeSuppliers(): Collection
    {
        return Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
    }
}

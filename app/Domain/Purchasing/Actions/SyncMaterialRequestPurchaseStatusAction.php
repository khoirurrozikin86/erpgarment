<?php

namespace App\Domain\Purchasing\Actions;

use App\Models\MaterialRequest;
use App\Models\PurchaseRequestItem;

class SyncMaterialRequestPurchaseStatusAction
{
    public function __invoke(MaterialRequest $materialRequest): MaterialRequest
    {
        $sourceItems = $materialRequest->items()->get(['id', 'quantity']);
        $allocations = PurchaseRequestItem::query()
            ->whereIn('material_request_item_id', $sourceItems->pluck('id'))
            ->whereHas('purchaseRequest', fn ($query) => $query->whereIn('status', ['pending', 'approved']))
            ->selectRaw('material_request_item_id, SUM(quantity) as allocated_quantity')
            ->groupBy('material_request_item_id')
            ->pluck('allocated_quantity', 'material_request_item_id');

        $anyAllocated = false;
        $allAllocated = $sourceItems->isNotEmpty();
        foreach ($sourceItems as $item) {
            $allocated = (float) ($allocations[$item->id] ?? 0);
            $required = (float) $item->quantity;
            $anyAllocated = $anyAllocated || $allocated > 0;
            $allAllocated = $allAllocated && $allocated + 0.0000001 >= $required;
        }

        $status = $allAllocated ? 'processed' : ($anyAllocated ? 'partially_processed' : 'not_processed');
        if ($materialRequest->purchase_status !== $status) {
            $materialRequest->update(['purchase_status' => $status]);
        }

        return $materialRequest->refresh();
    }
}

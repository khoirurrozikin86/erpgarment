<?php

namespace App\Domain\Purchasing\Queries;

use App\Models\MaterialRequest;
use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use Illuminate\Database\Eloquent\Builder;

class MaterialRequestTableQuery
{
    use AppliesDateRange;

    public function builder(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = MaterialRequest::query()
            ->with(['requester:id,name', 'reviewer:id,name'])
            ->select([
                'id', 'number', 'requested_by', 'department', 'needed_at', 'reason', 'status',
                'purchase_status', 'reviewed_by', 'reviewed_at', 'review_note', 'created_at', 'updated_at',
            ])
            ->withCount('items')
            ->orderByDesc('created_at');

        return $this->applyDateRange($query, 'created_at', $fromDate, $toDate);
    }

    public function details(MaterialRequest $request): MaterialRequest
    {
        return MaterialRequest::query()
            ->with(['requester', 'reviewer', 'items.product.unit'])
            ->findOrFail($request->id);
    }
}

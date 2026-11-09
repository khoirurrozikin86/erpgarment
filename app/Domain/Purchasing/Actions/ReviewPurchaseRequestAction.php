<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Purchasing\DTOs\PurchaseRequestReviewData;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewPurchaseRequestAction
{
    public function __construct(
        private AuditLogService $auditLog,
        private SyncMaterialRequestPurchaseStatusAction $syncPurchaseStatus,
    ) {}

    public function __invoke(PurchaseRequest $request, PurchaseRequestReviewData $data): PurchaseRequest
    {
        return DB::transaction(function () use ($request, $data) {
            $request = PurchaseRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($request->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Purchase Request ini sudah ditinjau.']);
            }
            if ((int) $request->requested_by === $data->reviewerId) {
                throw ValidationException::withMessages(['reviewer' => 'Pembuat tidak dapat menyetujui atau menolak Purchase Request-nya sendiri.']);
            }

            $oldValues = $request->toArray();
            $request->update([
                'status' => $data->status,
                'reviewed_by' => $data->reviewerId,
                'reviewed_at' => now(),
                'review_note' => $data->note,
            ]);
            $request->refresh();
            ($this->syncPurchaseStatus)($request->materialRequest()->firstOrFail());

            $this->auditLog->log(
                action: strtoupper($data->status), module: 'PURCHASE_REQUEST',
                description: "{$data->status} Purchase Request {$request->number}",
                model: $request, oldValues: $oldValues, newValues: $request->toArray(),
            );

            return $request->load(['materialRequest', 'supplier', 'requester', 'reviewer', 'items.product.unit']);
        });
    }
}

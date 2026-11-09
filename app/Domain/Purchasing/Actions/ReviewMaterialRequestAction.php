<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Purchasing\DTOs\MaterialRequestReviewData;
use App\Models\MaterialRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewMaterialRequestAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(MaterialRequest $request, MaterialRequestReviewData $data): MaterialRequest
    {
        return DB::transaction(function () use ($request, $data) {
            $request = MaterialRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($request->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Permintaan ini sudah ditinjau.']);
            }
            if ((int) $request->requested_by === $data->reviewerId) {
                throw ValidationException::withMessages(['reviewer' => 'Pemohon tidak dapat menyetujui atau menolak permintaannya sendiri.']);
            }

            $oldValues = $request->toArray();
            $request->update([
                'status' => $data->status,
                'reviewed_by' => $data->reviewerId,
                'reviewed_at' => now(),
                'review_note' => $data->note,
            ]);
            $request->refresh();

            $this->auditLog->log(
                action: strtoupper($data->status), module: 'MATERIAL_REQUEST',
                description: "{$data->status} Material Request {$request->number}",
                model: $request, oldValues: $oldValues, newValues: $request->toArray(),
            );

            return $request->load(['reviewer', 'requester', 'items.product.unit']);
        });
    }
}

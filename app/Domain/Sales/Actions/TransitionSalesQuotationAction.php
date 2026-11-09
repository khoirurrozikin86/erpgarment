<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\SalesQuotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionSalesQuotationAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(SalesQuotation $quotation, string $status, ?string $note, int $userId): SalesQuotation
    {
        return DB::transaction(function () use ($quotation, $status, $note, $userId) {
            $quotation = SalesQuotation::query()->lockForUpdate()->findOrFail($quotation->id);
            if ($status === 'sent' && $quotation->status !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Hanya quotation draft yang dapat diterbitkan.']);
            }
            if (in_array($status, ['accepted', 'rejected'], true) && $quotation->status !== 'sent') {
                throw ValidationException::withMessages(['status' => 'Hanya quotation yang sudah diterbitkan dapat ditinjau.']);
            }
            if ($status === 'accepted' && $quotation->valid_until && $quotation->valid_until->toDateString() < today()->toDateString()) {
                throw ValidationException::withMessages(['status' => 'Masa berlaku quotation sudah berakhir.']);
            }

            $oldStatus = $quotation->status;
            $quotation->update([
                'status' => $status,
                'issued_by' => $status === 'sent' ? $userId : $quotation->issued_by,
                'issued_at' => $status === 'sent' ? now() : $quotation->issued_at,
                'reviewed_by' => in_array($status, ['accepted', 'rejected'], true) ? $userId : $quotation->reviewed_by,
                'reviewed_at' => in_array($status, ['accepted', 'rejected'], true) ? now() : $quotation->reviewed_at,
                'review_note' => in_array($status, ['accepted', 'rejected'], true) ? $note : $quotation->review_note,
            ]);
            $this->auditLog->log(action: strtoupper($status), module: 'SALES_QUOTATION', description: "Mengubah status quotation {$quotation->number} menjadi {$status}", model: $quotation, oldValues: ['status' => $oldStatus], newValues: ['status' => $status, 'note' => $note]);
            return $quotation->load(['customer', 'creator', 'issuer', 'reviewer', 'items.product.unit']);
        });
    }
}

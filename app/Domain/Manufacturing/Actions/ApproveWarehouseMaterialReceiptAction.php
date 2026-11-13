<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\MaterialIssueDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveWarehouseMaterialReceiptAction
{
    public function __construct(private AuditLogService $audit) {}

    public function __invoke(MaterialIssueDocument $document, int $companyId, int $userId): MaterialIssueDocument
    {
        return DB::transaction(function () use ($document, $companyId, $userId) {
            $locked = MaterialIssueDocument::query()->where('company_id', $companyId)->lockForUpdate()->findOrFail($document->id);
            if ($locked->production_receipt_status === 'received') {
                throw ValidationException::withMessages(['receipt' => __('ui.production_receipt.already_received')]);
            }
            if (!$locked->raw_material_order_document_id || !$locked->items()->exists()) {
                throw ValidationException::withMessages(['receipt' => __('ui.production_receipt.invalid_source')]);
            }

            $oldValues = ['production_receipt_status' => $locked->production_receipt_status];
            $locked->update([
                'production_receipt_status' => 'received',
                'production_received_by' => $userId,
                'production_received_at' => now(),
            ]);
            $this->audit->log(
                action: 'APPROVE', module: 'PRODUCTION_RECEIPT',
                description: __('ui.production_receipt.audit', ['number' => $locked->number]),
                model: $locked,
                oldValues: $oldValues,
                newValues: ['production_receipt_status' => $locked->production_receipt_status, 'production_received_by' => $userId, 'production_received_at' => $locked->production_received_at],
                userId: $userId,
            );

            return $locked->load(['productionReceiver:id,name']);
        });
    }
}

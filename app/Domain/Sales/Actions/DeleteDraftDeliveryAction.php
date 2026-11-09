<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\Delivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteDraftDeliveryAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(Delivery $delivery): void
    {
        DB::transaction(function () use ($delivery) {
            $delivery = Delivery::query()->lockForUpdate()->findOrFail($delivery->id);
            if ($delivery->status !== 'draft') throw ValidationException::withMessages(['status' => 'Hanya Delivery draft yang dapat dihapus.']);
            $oldValues = $delivery->load('items')->toArray();
            $this->auditLog->log(action: 'DELETE', module: 'DELIVERY', description: "Menghapus draft Delivery {$delivery->number}", model: $delivery, oldValues: $oldValues, newValues: null);
            $delivery->delete();
        });
    }
}

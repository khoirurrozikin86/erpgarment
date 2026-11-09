<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmSalesOrderAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(SalesOrder $order, int $userId): SalesOrder
    {
        return DB::transaction(function () use ($order, $userId) {
            $order = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($order->status !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Hanya Sales Order draft yang dapat dikonfirmasi.']);
            }
            $order->update(['status' => 'confirmed', 'confirmed_by' => $userId, 'confirmed_at' => now()]);
            $this->auditLog->log(action: 'CONFIRM', module: 'SALES_ORDER', description: "Mengonfirmasi Sales Order {$order->number}", model: $order, oldValues: ['status' => 'draft'], newValues: ['status' => 'confirmed']);
            return $order->load(['quotation', 'customer', 'creator', 'confirmer', 'items.product.unit']);
        });
    }
}

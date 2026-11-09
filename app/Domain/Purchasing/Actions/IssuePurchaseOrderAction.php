<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IssuePurchaseOrderAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order) {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($order->status !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Hanya Purchase Order berstatus draft yang dapat diterbitkan.']);
            }

            $oldValues = $order->toArray();
            $order->update(['status' => 'issued', 'issued_at' => now()]);
            $order->refresh();

            $this->auditLog->log(
                action: 'ISSUE', module: 'PURCHASE_ORDER',
                description: "Menerbitkan Purchase Order {$order->number}",
                model: $order, oldValues: $oldValues, newValues: $order->toArray(),
            );

            return $order->load(['purchaseRequest', 'supplier', 'creator', 'items.product.unit']);
        });
    }
}

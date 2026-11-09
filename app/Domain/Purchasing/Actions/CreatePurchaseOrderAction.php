<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Purchasing\DTOs\PurchaseOrderData;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePurchaseOrderAction
{
    public function __construct(
        private DocumentNumberService $numbers,
        private AuditLogService $auditLog,
    ) {}

    public function __invoke(PurchaseOrderData $data, int $userId): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $purchaseRequest = PurchaseRequest::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($data->purchaseRequestId);

            if ($purchaseRequest->status !== 'approved') {
                throw ValidationException::withMessages(['purchase_request_id' => 'Purchase Request harus disetujui sebelum dibuatkan Purchase Order.']);
            }
            if ($purchaseRequest->purchaseOrder()->exists()) {
                throw ValidationException::withMessages(['purchase_request_id' => 'Purchase Request ini sudah memiliki Purchase Order.']);
            }
            if ($purchaseRequest->items->isEmpty()) {
                throw ValidationException::withMessages(['purchase_request_id' => 'Purchase Request tidak memiliki barang untuk dipesan.']);
            }

            $order = PurchaseOrder::create([
                'number' => $this->numbers->generate('purchase_order'),
                'purchase_request_id' => $purchaseRequest->id,
                'supplier_id' => $purchaseRequest->supplier_id,
                'created_by' => $userId,
                'order_date' => today(),
                'expected_delivery_at' => $data->expectedDeliveryAt,
                'payment_terms' => $data->paymentTerms,
                'notes' => $data->notes,
                'total_amount' => $purchaseRequest->total_amount,
                'status' => 'draft',
            ]);

            foreach ($purchaseRequest->items as $item) {
                $order->items()->create([
                    'purchase_request_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'received_quantity' => 0,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ]);
            }

            $this->auditLog->log(
                action: 'CREATE', module: 'PURCHASE_ORDER',
                description: "Membuat Purchase Order {$order->number}",
                model: $order, oldValues: null, newValues: $order->load('items')->toArray(),
            );

            return $order->load(['purchaseRequest', 'supplier', 'creator', 'items.product.unit']);
        });
    }
}

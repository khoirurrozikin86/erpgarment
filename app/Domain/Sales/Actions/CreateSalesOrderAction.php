<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Sales\DTOs\SalesOrderData;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\SalesOrder;
use App\Models\SalesQuotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSalesOrderAction
{
    public function __construct(private DocumentNumberService $numbers, private AuditLogService $auditLog) {}

    public function __invoke(SalesOrderData $data, int $userId): SalesOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $attributes = $data->attributes;
            $quotation = SalesQuotation::query()->with('items')->lockForUpdate()->findOrFail($attributes['sales_quotation_id']);
            if ($quotation->status !== 'accepted') {
                throw ValidationException::withMessages(['sales_quotation_id' => 'Sales Order hanya dapat dibuat dari quotation yang disetujui customer.']);
            }
            if ($quotation->salesOrder()->exists()) {
                throw ValidationException::withMessages(['sales_quotation_id' => 'Quotation ini sudah memiliki Sales Order.']);
            }
            if ($quotation->items->isEmpty()) {
                throw ValidationException::withMessages(['sales_quotation_id' => 'Quotation tidak memiliki barang.']);
            }

            $order = SalesOrder::create([
                'number' => $this->numbers->generate('sales_order'),
                'sales_quotation_id' => $quotation->id,
                'customer_id' => $quotation->customer_id,
                'created_by' => $userId,
                'order_date' => $attributes['order_date'],
                'requested_delivery_at' => $attributes['requested_delivery_at'] ?? null,
                'subtotal' => $quotation->subtotal,
                'tax_amount' => $quotation->tax_amount,
                'total_amount' => $quotation->total_amount,
                'notes' => $attributes['notes'] ?? $quotation->notes,
                'status' => 'draft',
            ]);

            foreach ($quotation->items as $quotationItem) {
                $order->items()->create([
                    'sales_quotation_item_id' => $quotationItem->id,
                    'product_id' => $quotationItem->product_id,
                    'quantity' => $quotationItem->quantity,
                    'unit_price' => $quotationItem->unit_price,
                    'discount_amount' => $quotationItem->discount_amount,
                    'tax_rate' => $quotationItem->tax_rate,
                    'line_total' => $quotationItem->line_total,
                ]);
            }

            $this->auditLog->log(action: 'CREATE', module: 'SALES_ORDER', description: "Membuat Sales Order {$order->number} dari quotation {$quotation->number}", model: $order, oldValues: null, newValues: $order->load('items')->toArray());
            return $order->load(['quotation', 'customer', 'creator', 'items.product.unit']);
        });
    }
}

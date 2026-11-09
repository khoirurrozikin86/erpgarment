<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Sales\DTOs\SalesInvoiceData;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\Delivery;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSalesInvoiceAction
{
    public function __construct(private DocumentNumberService $numbers, private AuditLogService $auditLog) {}

    public function __invoke(SalesInvoiceData $data, int $userId): SalesInvoice
    {
        return DB::transaction(function () use ($data, $userId) {
            $attributes = $data->attributes;
            $delivery = Delivery::query()->lockForUpdate()->findOrFail($attributes['delivery_id']);
            if ($delivery->status !== 'posted') {
                throw ValidationException::withMessages(['delivery_id' => 'Sales Invoice hanya dapat dibuat dari Delivery yang sudah diposting.']);
            }
            if ($delivery->salesInvoice()->exists()) {
                throw ValidationException::withMessages(['delivery_id' => 'Delivery ini sudah memiliki Sales Invoice.']);
            }
            $deliveryItems = $delivery->items()->with('salesOrderItem')->lockForUpdate()->get();
            if ($deliveryItems->isEmpty()) {
                throw ValidationException::withMessages(['delivery_id' => 'Delivery tidak memiliki barang.']);
            }

            $invoice = SalesInvoice::create([
                'number' => $this->numbers->generate('sales_invoice'),
                'delivery_id' => $delivery->id,
                'sales_order_id' => $delivery->sales_order_id,
                'customer_id' => $delivery->customer_id,
                'created_by' => $userId,
                'invoice_date' => $attributes['invoice_date'],
                'due_date' => $attributes['due_date'] ?? null,
                'notes' => $attributes['notes'] ?? null,
                'status' => 'draft',
            ]);

            $subtotal = 0;
            $taxAmount = 0;
            foreach ($deliveryItems as $deliveryItem) {
                $orderItem = $deliveryItem->salesOrderItem;
                if (!$orderItem) throw ValidationException::withMessages(['items' => 'Detail Sales Order tidak ditemukan.']);
                $quantity = round((float) $deliveryItem->quantity, 4);
                $gross = round($quantity * (float) $orderItem->unit_price, 2);
                $discount = (float) $orderItem->quantity > 0
                    ? round((float) $orderItem->discount_amount * $quantity / (float) $orderItem->quantity, 2)
                    : 0;
                $net = max(0, round($gross - $discount, 2));
                $taxRate = (float) $orderItem->tax_rate;
                $tax = round($net * $taxRate / 100, 2);
                $lineTotal = round($net + $tax, 2);
                $subtotal += $net;
                $taxAmount += $tax;

                $invoice->items()->create([
                    'delivery_item_id' => $deliveryItem->id,
                    'sales_order_item_id' => $orderItem->id,
                    'product_id' => $deliveryItem->product_id,
                    'quantity' => $quantity,
                    'unit_price' => $orderItem->unit_price,
                    'discount_amount' => $discount,
                    'tax_rate' => $taxRate,
                    'line_total' => $lineTotal,
                ]);
            }
            $invoice->update([
                'subtotal' => round($subtotal, 2),
                'tax_amount' => round($taxAmount, 2),
                'total_amount' => round($subtotal + $taxAmount, 2),
            ]);

            $this->auditLog->log(action: 'CREATE', module: 'SALES_INVOICE', description: "Membuat Sales Invoice {$invoice->number} dari Delivery {$delivery->number}", model: $invoice, oldValues: null, newValues: $invoice->load('items')->toArray());
            return $invoice->load(['delivery', 'salesOrder', 'customer', 'creator', 'items.product.unit']);
        });
    }
}

<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Sales\DTOs\DeliveryData;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDeliveryAction
{
    public function __construct(private DocumentNumberService $numbers, private AuditLogService $auditLog) {}

    public function __invoke(DeliveryData $data, int $userId): Delivery
    {
        return DB::transaction(function () use ($data, $userId) {
            $attributes = $data->attributes;
            $order = SalesOrder::query()->lockForUpdate()->findOrFail($attributes['sales_order_id']);
            if (!in_array($order->status, ['confirmed', 'partially_delivered'], true)) {
                throw ValidationException::withMessages(['sales_order_id' => 'Delivery hanya dapat dibuat untuk Sales Order yang telah dikonfirmasi dan belum selesai.']);
            }

            $orderItems = SalesOrderItem::query()->where('sales_order_id', $order->id)->orderBy('product_id')->lockForUpdate()->get()->keyBy('id');
            $requestedItems = collect($attributes['items'])->filter(fn ($item) => (float) $item['quantity'] > 0)->keyBy(fn ($item) => (int) $item['sales_order_item_id']);
            if ($requestedItems->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Masukkan jumlah kirim minimal satu barang.']);
            }

            $reserved = DeliveryItem::query()
                ->join('deliveries', 'deliveries.id', '=', 'delivery_items.delivery_id')
                ->where('deliveries.sales_order_id', $order->id)
                ->where('deliveries.status', 'draft')
                ->selectRaw('delivery_items.sales_order_item_id, SUM(delivery_items.quantity) as reserved_quantity')
                ->groupBy('delivery_items.sales_order_item_id')
                ->pluck('reserved_quantity', 'sales_order_item_id');

            foreach ($requestedItems as $itemId => $requested) {
                $orderItem = $orderItems->get($itemId);
                if (!$orderItem) {
                    throw ValidationException::withMessages(['items' => 'Barang yang dipilih tidak termasuk dalam Sales Order ini.']);
                }
                $remaining = round((float) $orderItem->quantity - (float) $orderItem->delivered_quantity - (float) ($reserved[$itemId] ?? 0), 4);
                if ((float) $requested['quantity'] <= 0 || (float) $requested['quantity'] > $remaining) {
                    throw ValidationException::withMessages(['items' => "Jumlah pengiriman melebihi sisa pesanan untuk {$orderItem->product()->value('name')} (sisa: {$remaining})."]);
                }
            }

            $delivery = Delivery::create([
                'number' => $this->numbers->generate('delivery_order'),
                'sales_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'delivered_by' => $userId,
                'delivered_at' => $attributes['delivered_at'],
                'status' => 'draft',
                'notes' => $attributes['notes'] ?? null,
            ]);

            foreach ($requestedItems as $itemId => $requested) {
                $orderItem = $orderItems->get($itemId);
                $delivery->items()->create([
                    'sales_order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'quantity' => round((float) $requested['quantity'], 4),
                ]);
            }

            $this->auditLog->log(action: 'CREATE', module: 'DELIVERY', description: "Membuat Delivery {$delivery->number} untuk SO {$order->number}", model: $delivery, oldValues: null, newValues: $delivery->load('items')->toArray());
            return $delivery->load(['salesOrder', 'customer', 'deliverer', 'items.product.unit']);
        });
    }
}

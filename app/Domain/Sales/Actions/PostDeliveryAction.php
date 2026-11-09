<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostDeliveryAction
{
    public function __construct(private AuditLogService $auditLog, private AutomaticJournalService $journals) {}

    public function __invoke(Delivery $delivery, int $userId): Delivery
    {
        return DB::transaction(function () use ($delivery, $userId) {
            $delivery = Delivery::query()->lockForUpdate()->findOrFail($delivery->id);
            if ($delivery->status !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Delivery ini sudah diposting.']);
            }
            $order = SalesOrder::query()->lockForUpdate()->findOrFail($delivery->sales_order_id);
            if (!in_array($order->status, ['confirmed', 'partially_delivered'], true)) {
                throw ValidationException::withMessages(['sales_order_id' => 'Sales Order tidak lagi dapat dikirim.']);
            }

            $items = DeliveryItem::query()->where('delivery_id', $delivery->id)->orderBy('product_id')->lockForUpdate()->get();
            if ($items->isEmpty()) throw ValidationException::withMessages(['items' => 'Delivery tidak memiliki barang.']);
            $orderItems = SalesOrderItem::query()->whereIn('id', $items->pluck('sales_order_item_id'))->orderBy('product_id')->lockForUpdate()->get()->keyBy('id');
            $productIds = $items->pluck('product_id')->unique()->sort()->values();
            $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                $orderItem = $orderItems->get($item->sales_order_item_id);
                $product = $products->get($item->product_id);
                if (!$orderItem || !$product) throw ValidationException::withMessages(['items' => 'Barang Delivery atau Sales Order sudah tidak tersedia.']);
                $remaining = round((float) $orderItem->quantity - (float) $orderItem->delivered_quantity, 4);
                $quantity = round((float) $item->quantity, 4);
                if ($quantity <= 0 || $quantity > $remaining) {
                    throw ValidationException::withMessages(['items' => "Jumlah {$product->name} melebihi sisa Sales Order (sisa: {$remaining})."]);
                }

                if ($product->track_stock) {
                    $stock = ProductStock::query()->where('product_id', $product->id)->lockForUpdate()->first();
                    $before = round((float) ($stock?->quantity ?? 0), 4);
                    if ($before < $quantity) {
                        throw ValidationException::withMessages(['items' => "Stok {$product->code} — {$product->name} tidak cukup. Tersedia {$before}, diminta {$quantity}. Penerimaan barang harus dicatat sebelum Delivery diposting."]);
                    }
                    $after = round($before - $quantity, 4);
                    $unitCost = (float) $stock->average_unit_cost > 0 ? (float) $stock->average_unit_cost : (float) $product->purchase_price;
                    if ($unitCost <= 0) {
                        throw ValidationException::withMessages(['items' => "Barang {$product->code} belum memiliki biaya persediaan. Isi harga beli atau catat penerimaan barang terlebih dahulu."]);
                    }
                    $costAmount = round($quantity * $unitCost, 2);
                    $stock->update(['quantity' => $after]);
                    $movement = StockMovement::create([
                        'product_id' => $product->id,
                        'delivery_id' => $delivery->id,
                        'delivery_item_id' => $item->id,
                        'created_by' => $userId,
                        'movement_type' => 'sales_delivery',
                        'quantity' => -$quantity,
                        'quantity_before' => $before,
                        'quantity_after' => $after,
                        'cost_amount' => $costAmount,
                        'notes' => "Pengiriman dari Delivery {$delivery->number}",
                    ]);
                    $this->journals->recordStockMovement($movement, $userId);
                }

                $orderItem->update(['delivered_quantity' => round((float) $orderItem->delivered_quantity + $quantity, 4)]);
            }

            $orderItems = SalesOrderItem::query()->where('sales_order_id', $order->id)->get();
            $isComplete = $orderItems->every(fn (SalesOrderItem $item) => (float) $item->delivered_quantity >= (float) $item->quantity);
            $oldStatus = $order->status;
            $order->update(['status' => $isComplete ? 'delivered' : 'partially_delivered']);
            $delivery->update(['status' => 'posted', 'posted_at' => now()]);

            $this->auditLog->log(action: 'POST', module: 'DELIVERY', description: "Memposting Delivery {$delivery->number}", model: $delivery, oldValues: ['sales_order_status' => $oldStatus], newValues: ['delivery' => $delivery->toArray(), 'sales_order_status' => $order->status, 'items' => $items->load('product')->toArray()]);
            return $delivery->load(['salesOrder', 'customer', 'deliverer', 'items.product.unit']);
        });
    }
}

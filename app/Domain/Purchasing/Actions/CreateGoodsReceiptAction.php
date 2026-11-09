<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Purchasing\DTOs\GoodsReceiptData;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateGoodsReceiptAction
{
    public function __construct(
        private DocumentNumberService $numbers,
        private AuditLogService $auditLog,
        private AutomaticJournalService $journals,
    ) {}

    public function __invoke(GoodsReceiptData $data, int $userId): GoodsReceipt
    {
        return DB::transaction(function () use ($data, $userId) {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($data->purchaseOrderId);
            if (!in_array($order->status, ['issued', 'partially_received'], true)) {
                throw ValidationException::withMessages(['purchase_order_id' => 'Penerimaan hanya dapat dicatat untuk PO yang sudah diterbitkan dan belum selesai.']);
            }

            $orderItems = PurchaseOrderItem::query()
                ->where('purchase_order_id', $order->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $receivedItems = collect($data->items)
                ->filter(fn (array $item) => (float) $item['quantity'] > 0)
                ->values();
            if ($receivedItems->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Masukkan jumlah diterima untuk minimal satu barang.']);
            }

            foreach ($receivedItems as $item) {
                $poItem = $orderItems->get((int) $item['purchase_order_item_id']);
                if (!$poItem) {
                    throw ValidationException::withMessages(['items' => 'Barang yang dipilih tidak termasuk dalam Purchase Order ini.']);
                }

                $remaining = round((float) $poItem->quantity - (float) $poItem->received_quantity, 4);
                if ((float) $item['quantity'] > $remaining) {
                    throw ValidationException::withMessages([
                        'items' => "Jumlah penerimaan melebihi sisa pesanan barang pada PO {$order->number}.",
                    ]);
                }
            }

            $receipt = GoodsReceipt::create([
                'number' => $this->numbers->generate('goods_receipt'),
                'purchase_order_id' => $order->id,
                'supplier_id' => $order->supplier_id,
                'received_by' => $userId,
                'received_at' => $data->receivedAt,
                'notes' => $data->notes,
            ]);

            foreach ($receivedItems as $item) {
                $poItem = $orderItems->get((int) $item['purchase_order_item_id']);
                $product = Product::query()->lockForUpdate()->findOrFail($poItem->product_id);
                $quantity = round((float) $item['quantity'], 4);
                $lineTotal = round($quantity * (float) $poItem->unit_price, 2);

                $receiptItem = $receipt->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $poItem->unit_price,
                    'line_total' => $lineTotal,
                ]);

                $poItem->received_quantity = round((float) $poItem->received_quantity + $quantity, 4);
                $poItem->save();

                if ($product->track_stock) {
                    $stock = ProductStock::query()->firstOrCreate(
                        ['product_id' => $product->id],
                        ['quantity' => 0],
                    );
                    $stock = ProductStock::query()->lockForUpdate()->findOrFail($stock->id);
                    $before = round((float) $stock->quantity, 4);
                    $after = round($before + $quantity, 4);
                    $averageCost = (float) $stock->average_unit_cost > 0 ? (float) $stock->average_unit_cost : (float) $product->purchase_price;
                    $receiptValue = $lineTotal;
                    $newAverageCost = $after > 0
                        ? round((($before * $averageCost) + $receiptValue) / $after, 6)
                        : 0;
                    $stock->update(['quantity' => $after, 'average_unit_cost' => $newAverageCost]);

                    $movement = StockMovement::create([
                        'product_id' => $product->id,
                        'goods_receipt_id' => $receipt->id,
                        'goods_receipt_item_id' => $receiptItem->id,
                        'created_by' => $userId,
                        'movement_type' => 'purchase_receipt',
                        'quantity' => $quantity,
                        'quantity_before' => $before,
                        'quantity_after' => $after,
                        'cost_amount' => $receiptValue,
                        'notes' => "Penerimaan {$receipt->number}",
                    ]);
                    $this->journals->recordStockMovement($movement, $userId);
                } else {
                    $this->journals->recordNonStockReceipt($receiptItem, $receipt, $userId);
                }
            }

            $orderItems->each->refresh();
            $allReceived = $orderItems->every(fn (PurchaseOrderItem $item) =>
                (float) $item->received_quantity >= (float) $item->quantity
            );
            $oldStatus = $order->status;
            $order->update(['status' => $allReceived ? 'received' : 'partially_received']);

            $this->auditLog->log(
                action: 'RECEIVE',
                module: 'GOODS_RECEIPT',
                description: "Mencatat penerimaan {$receipt->number} untuk PO {$order->number}",
                model: $receipt,
                oldValues: ['purchase_order_status' => $oldStatus],
                newValues: [
                    'receipt' => $receipt->load('items')->toArray(),
                    'purchase_order_status' => $order->status,
                ],
            );

            return $receipt->load([
                'purchaseOrder', 'supplier', 'receiver', 'items.product.unit',
            ]);
        });
    }
}

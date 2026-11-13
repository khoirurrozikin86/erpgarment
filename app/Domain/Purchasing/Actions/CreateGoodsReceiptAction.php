<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Purchasing\DTOs\GoodsReceiptData;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateGoodsReceiptAction
{
    public function __construct(
        private DocumentNumberService $numbers,
        private AuditLogService $auditLog,
    ) {}

    public function __invoke(GoodsReceiptData $data, int $userId): GoodsReceipt
    {
        return DB::transaction(function () use ($data, $userId) {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($data->purchaseOrderId);
            if (!in_array($order->status, ['issued', 'partially_received'], true)) {
                throw ValidationException::withMessages(['purchase_order_id' => 'Penerimaan hanya dapat dicatat untuk PO yang sudah diterbitkan dan belum selesai.']);
            }

            $orderItems = PurchaseOrderItem::query()->where('purchase_order_id', $order->id)
                ->lockForUpdate()->get()->keyBy('id');
            $receivedItems = collect($data->items)->filter(fn (array $item) => (float) $item['quantity'] > 0)->values();
            if ($receivedItems->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Masukkan jumlah diterima untuk minimal satu barang.']);
            }

            $receivedTotals = $receivedItems->groupBy(fn (array $item) => (int) $item['purchase_order_item_id'])
                ->map(fn ($items) => $items->sum(fn (array $item) => (float) $item['quantity']));
            foreach ($receivedTotals as $poItemId => $totalQuantity) {
                $poItem = $orderItems->get((int) $poItemId);
                if (!$poItem) {
                    throw ValidationException::withMessages(['items' => 'Barang yang dipilih tidak termasuk dalam Purchase Order ini.']);
                }
                $remaining = round((float) $poItem->quantity - (float) $poItem->received_quantity, 4);
                if ((float) $totalQuantity > $remaining) {
                    throw ValidationException::withMessages(['items' => "Jumlah penerimaan melebihi sisa pesanan barang pada PO {$order->number}."]);
                }
            }

            $receipt = GoodsReceipt::create([
                'number' => $this->numbers->generate('goods_receipt'),
                'purchase_order_id' => $order->id,
                'supplier_id' => $order->supplier_id,
                'received_by' => $userId,
                'received_at' => $data->receivedAt,
                'invoice_number' => $data->invoiceNumber,
                'invoice_date' => $data->invoiceDate,
                'currency' => $data->currency,
                'customs_document_code' => $data->customsDocumentCode,
                'customs_document_number' => $data->customsDocumentNumber,
                'customs_document_date' => $data->customsDocumentDate,
                'notes' => $data->notes,
                'status' => 'pending',
            ]);

            foreach ($receivedItems as $item) {
                $poItem = $orderItems->get((int) $item['purchase_order_item_id']);
                $product = Product::query()->findOrFail($poItem->product_id);
                $quantity = round((float) $item['quantity'], 4);
                $lotNumber = trim((string) ($item['lot_number'] ?? '')) ?: null;
                if ($product->track_stock && !$lotNumber) {
                    throw ValidationException::withMessages(['items' => __('ui.workflow.lot_number_required')]);
                }

                $receipt->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'product_id' => $product->id,
                    'lot_number' => $lotNumber,
                    'quantity' => $quantity,
                    'unit_price' => $poItem->unit_price,
                    'line_total' => round($quantity * (float) $poItem->unit_price, 2),
                ]);

                // Reserve the physical receipt quantity against its PO while stock waits for approval.
                $poItem->received_quantity = round((float) $poItem->received_quantity + $quantity, 4);
                $poItem->save();
            }

            $orderItems->each->refresh();
            $allReceived = $orderItems->every(fn (PurchaseOrderItem $item) => (float) $item->received_quantity >= (float) $item->quantity);
            $oldStatus = $order->status;
            $order->update(['status' => $allReceived ? 'received' : 'partially_received']);

            $this->auditLog->log(
                action: 'RECEIVE',
                module: 'GOODS_RECEIPT',
                description: "Mencatat penerimaan {$receipt->number} untuk PO {$order->number}; menunggu persetujuan",
                model: $receipt,
                oldValues: ['purchase_order_status' => $oldStatus],
                newValues: ['receipt' => $receipt->load('items')->toArray(), 'purchase_order_status' => $order->status],
            );

            return $receipt->load(['purchaseOrder', 'supplier', 'receiver', 'items.product.unit']);
        });
    }
}

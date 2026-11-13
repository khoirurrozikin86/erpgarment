<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryLot;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteGoodsReceiptAction
{
    public function __construct(
        private AutomaticJournalService $journals,
        private AuditLogService $auditLog,
    ) {}

    public function __invoke(GoodsReceipt $goodsReceipt, int $userId): void
    {
        DB::transaction(function () use ($goodsReceipt, $userId) {
            $receipt = GoodsReceipt::query()->with('items')->lockForUpdate()->findOrFail($goodsReceipt->id);
            if (!in_array($receipt->status, ['pending', 'approved'], true)) {
                throw ValidationException::withMessages(['receipt' => __('ui.workflow.receipt_delete_invalid_status')]);
            }

            if ($receipt->purchaseReturns()->exists()) {
                throw ValidationException::withMessages(['receipt' => __('ui.workflow.receipt_delete_has_returns')]);
            }
            if ($receipt->supplierPayments()->exists()) {
                throw ValidationException::withMessages(['receipt' => __('ui.workflow.receipt_delete_has_payments')]);
            }

            $movements = StockMovement::query()->where('goods_receipt_id', $receipt->id)
                ->with('lotAllocations')->lockForUpdate()->get();

            foreach ($movements->groupBy('product_id') as $productId => $productMovements) {
                $lastReceiptMovementId = (int) $productMovements->max('id');
                $hasLaterMovement = StockMovement::query()
                    ->where('product_id', $productId)
                    ->where('id', '>', $lastReceiptMovementId)
                    ->exists();
                if ($hasLaterMovement) {
                    throw ValidationException::withMessages(['receipt' => __('ui.workflow.receipt_delete_has_later_stock')]);
                }
            }

            $receiptItems = $receipt->items->groupBy('product_id');
            $trackedProductIds = $movements->pluck('product_id')->unique();
            foreach ($trackedProductIds as $productId) {
                $items = $receiptItems->get($productId, collect());
                $quantity = $items->sum(fn (GoodsReceiptItem $item) => (float) $item->quantity);
                $stock = ProductStock::query()->where('product_id', $productId)->lockForUpdate()->first();
                if (!$stock || (float) $stock->quantity + 0.0000001 < $quantity) {
                    throw ValidationException::withMessages(['receipt' => __('ui.workflow.receipt_delete_insufficient_stock')]);
                }
            }

            $lotQuantities = $receipt->items->whereNotNull('inventory_lot_id')
                ->groupBy('inventory_lot_id')
                ->map(fn ($items) => $items->sum(fn (GoodsReceiptItem $item) => (float) $item->quantity));
            foreach ($lotQuantities as $lotId => $quantity) {
                $lot = InventoryLot::query()->lockForUpdate()->findOrFail($lotId);
                if ((float) $lot->current_quantity + 0.0000001 < $quantity || (float) $lot->initial_quantity + 0.0000001 < $quantity) {
                    throw ValidationException::withMessages(['receipt' => __('ui.workflow.receipt_delete_lot_unavailable')]);
                }
            }

            $oldValues = $receipt->toArray();
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($receipt->purchase_order_id);
            $receivedByPoItem = $receipt->items->groupBy('purchase_order_item_id')
                ->map(fn ($items) => $items->sum(fn (GoodsReceiptItem $item) => (float) $item->quantity));
            $poItems = PurchaseOrderItem::query()->whereIn('id', $receivedByPoItem->keys())->lockForUpdate()->get()->keyBy('id');

            if ($receipt->status === 'approved') {
                $this->journals->reverseGoodsReceiptJournals($receipt, $userId);
            }

            foreach ($trackedProductIds as $productId) {
                $items = $receiptItems->get($productId, collect());
                $quantity = $items->sum(fn (GoodsReceiptItem $item) => (float) $item->quantity);
                $receiptValue = $items->sum(fn (GoodsReceiptItem $item) => (float) $item->line_total);
                $stock = ProductStock::query()->where('product_id', $productId)->lockForUpdate()->first();
                if ($stock) {
                    $beforeQuantity = (float) $stock->quantity;
                    $afterQuantity = round($beforeQuantity - $quantity, 4);
                    $beforeAverage = (float) $stock->average_unit_cost;
                    $afterAverage = $afterQuantity > 0
                        ? max(0, round((($beforeQuantity * $beforeAverage) - $receiptValue) / $afterQuantity, 6))
                        : 0;
                    $stock->update(['quantity' => $afterQuantity, 'average_unit_cost' => $afterAverage]);
                }
            }

            foreach ($lotQuantities as $lotId => $quantity) {
                $lot = InventoryLot::query()->lockForUpdate()->findOrFail($lotId);
                $lot->update([
                    'initial_quantity' => round((float) $lot->initial_quantity - $quantity, 4),
                    'current_quantity' => round((float) $lot->current_quantity - $quantity, 4),
                ]);
            }

            foreach ($receivedByPoItem as $poItemId => $quantity) {
                $poItem = $poItems->get((int) $poItemId);
                if (!$poItem || (float) $poItem->received_quantity + 0.0000001 < $quantity) {
                    throw ValidationException::withMessages(['receipt' => __('ui.workflow.receipt_delete_po_mismatch')]);
                }
                $poItem->update(['received_quantity' => round((float) $poItem->received_quantity - $quantity, 4)]);
            }

            $poItems = PurchaseOrderItem::query()->where('purchase_order_id', $order->id)->get();
            $allReceived = $poItems->isNotEmpty() && $poItems->every(fn (PurchaseOrderItem $item) => (float) $item->received_quantity >= (float) $item->quantity);
            $someReceived = $poItems->contains(fn (PurchaseOrderItem $item) => (float) $item->received_quantity > 0);
            $order->update(['status' => $allReceived ? 'received' : ($someReceived ? 'partially_received' : 'issued')]);

            foreach ($movements as $movement) $movement->delete();

            $this->auditLog->log(
                action: 'DELETE',
                module: 'GOODS_RECEIPT',
                description: $receipt->status === 'approved'
                    ? "Menghapus penerimaan {$receipt->number} beserta pembalikan saldo stok dan jurnal terkait"
                    : "Menghapus penerimaan {$receipt->number} yang masih menunggu persetujuan",
                model: $receipt,
                oldValues: ['receipt' => $oldValues, 'items' => $receipt->items->toArray()],
                newValues: ['purchase_order_status' => $order->status],
                userId: $userId,
            );

            $receipt->delete();
        });
    }
}

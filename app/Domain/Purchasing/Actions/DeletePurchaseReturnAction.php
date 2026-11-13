<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryLot;
use App\Models\ProductStock;
use App\Models\PurchaseReturn;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeletePurchaseReturnAction
{
    public function __construct(
        private AutomaticJournalService $journals,
        private AuditLogService $auditLog,
    ) {}

    public function __invoke(PurchaseReturn $purchaseReturn, int $userId): void
    {
        DB::transaction(function () use ($purchaseReturn, $userId) {
            $return = PurchaseReturn::query()->with(['goodsReceipt', 'items.goodsReceiptItem', 'items.product'])
                ->lockForUpdate()->findOrFail($purchaseReturn->id);

            if (!$return->goodsReceipt) abort(404);

            $movements = StockMovement::query()->where('purchase_return_id', $return->id)
                ->with('lotAllocations')->lockForUpdate()->get();

            foreach ($movements->groupBy('product_id') as $productId => $productMovements) {
                $lastMovementId = (int) $productMovements->max('id');
                if (StockMovement::query()->where('product_id', $productId)->where('id', '>', $lastMovementId)->exists()) {
                    throw ValidationException::withMessages(['purchase_return' => __('ui.workflow.purchase_return_delete_has_later_stock')]);
                }
            }

            $returnItems = $return->items->groupBy('goods_receipt_item_id');
            $receiptItems = GoodsReceiptItem::query()->whereIn('id', $returnItems->keys())->lockForUpdate()->get()->keyBy('id');
            foreach ($returnItems as $receiptItemId => $items) {
                $receiptItem = $receiptItems->get((int) $receiptItemId);
                $quantity = $items->sum(fn ($item) => (float) $item->quantity);
                if (!$receiptItem || (float) $receiptItem->returned_quantity + 0.0000001 < $quantity) {
                    throw ValidationException::withMessages(['purchase_return' => __('ui.workflow.purchase_return_delete_quantity_mismatch')]);
                }
            }

            $movementsByProduct = $movements->groupBy('product_id');
            foreach ($movementsByProduct as $productId => $productMovements) {
                $quantity = $productMovements->sum(fn (StockMovement $movement) => abs((float) $movement->quantity));
                $stock = ProductStock::query()->where('product_id', $productId)->lockForUpdate()->first();
                if (!$stock) {
                    throw ValidationException::withMessages(['purchase_return' => __('ui.workflow.purchase_return_delete_stock_missing')]);
                }

                foreach ($productMovements as $movement) {
                    $allocated = $movement->lotAllocations->sum(fn ($allocation) => abs((float) $allocation->quantity));
                    if (abs($allocated - abs((float) $movement->quantity)) > 0.0001) {
                        throw ValidationException::withMessages(['purchase_return' => __('ui.workflow.purchase_return_delete_lot_mismatch')]);
                    }
                    foreach ($movement->lotAllocations as $allocation) {
                        $lot = InventoryLot::query()->lockForUpdate()->find($allocation->inventory_lot_id);
                        if (!$lot) {
                            throw ValidationException::withMessages(['purchase_return' => __('ui.workflow.purchase_return_delete_lot_missing')]);
                        }
                        $lot->update(['current_quantity' => round((float) $lot->current_quantity + abs((float) $allocation->quantity), 4)]);
                    }
                }

                $stock->update(['quantity' => round((float) $stock->quantity + $quantity, 4)]);
            }

            $oldValues = $return->toArray();
            $oldItems = $return->items->toArray();
            $this->journals->reversePurchaseReturnJournals($return, $userId);

            foreach ($returnItems as $receiptItemId => $items) {
                $receiptItem = $receiptItems->get((int) $receiptItemId);
                $quantity = $items->sum(fn ($item) => (float) $item->quantity);
                $receiptItem->update(['returned_quantity' => round((float) $receiptItem->returned_quantity - $quantity, 4)]);
            }

            foreach ($movements as $movement) $movement->delete();

            $this->auditLog->log(
                action: 'DELETE',
                module: 'PURCHASE_RETURN',
                description: "Menghapus retur pembelian {$return->number} beserta pemulihan stok dan jurnal pembalik",
                model: $return,
                oldValues: ['purchase_return' => $oldValues, 'items' => $oldItems],
                newValues: null,
                userId: $userId,
            );

            $return->delete();
        });
    }
}

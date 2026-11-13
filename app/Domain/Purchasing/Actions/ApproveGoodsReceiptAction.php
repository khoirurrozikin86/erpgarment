<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Models\GoodsReceipt;
use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveGoodsReceiptAction
{
    public function __construct(
        private AutomaticJournalService $journals,
        private AuditLogService $auditLog,
    ) {}

    public function __invoke(GoodsReceipt $goodsReceipt, int $userId): GoodsReceipt
    {
        return DB::transaction(function () use ($goodsReceipt, $userId) {
            $receipt = GoodsReceipt::query()->with('items')->lockForUpdate()->findOrFail($goodsReceipt->id);
            if ($receipt->status !== 'pending') {
                throw ValidationException::withMessages(['receipt' => __('ui.workflow.receipt_not_pending')]);
            }

            foreach ($receipt->items as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                $quantity = round((float) $item->quantity, 4);

                if (!$product->track_stock) {
                    $this->journals->recordNonStockReceipt($item, $receipt, $userId);
                    continue;
                }

                $lotNumber = trim((string) ($item->lot_number ?? ''));
                if ($lotNumber === '') {
                    throw ValidationException::withMessages(['items' => __('ui.workflow.lot_number_required')]);
                }

                $lot = InventoryLot::query()->firstOrCreate(
                    ['product_id' => $product->id, 'lot_number' => $lotNumber],
                    [
                        'supplier_id' => $receipt->supplier_id,
                        'received_at' => $receipt->received_at,
                        'initial_quantity' => 0,
                        'current_quantity' => 0,
                        'unit_cost' => $item->unit_price,
                        'source_type' => 'receipt',
                    ],
                );
                $lot = InventoryLot::query()->whereKey($lot->id)->lockForUpdate()->firstOrFail();
                if ((int) $lot->supplier_id !== (int) $receipt->supplier_id) {
                    throw ValidationException::withMessages(['items' => __('ui.workflow.lot_supplier_mismatch', ['lot' => $lotNumber])]);
                }
                $lot->update([
                    'initial_quantity' => round((float) $lot->initial_quantity + $quantity, 4),
                    'current_quantity' => round((float) $lot->current_quantity + $quantity, 4),
                ]);

                $item->update(['inventory_lot_id' => $lot->id]);
                $stock = ProductStock::query()->firstOrCreate(['product_id' => $product->id], ['quantity' => 0]);
                $stock = ProductStock::query()->lockForUpdate()->findOrFail($stock->id);
                $before = round((float) $stock->quantity, 4);
                $after = round($before + $quantity, 4);
                $averageCost = (float) $stock->average_unit_cost > 0 ? (float) $stock->average_unit_cost : (float) $product->purchase_price;
                $newAverageCost = $after > 0
                    ? round((($before * $averageCost) + (float) $item->line_total) / $after, 6)
                    : 0;
                $stock->update(['quantity' => $after, 'average_unit_cost' => $newAverageCost]);

                $movement = StockMovement::create([
                    'product_id' => $product->id,
                    'goods_receipt_id' => $receipt->id,
                    'goods_receipt_item_id' => $item->id,
                    'created_by' => $userId,
                    'movement_type' => 'purchase_receipt',
                    'quantity' => $quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'cost_amount' => $item->line_total,
                    'notes' => "Penerimaan {$receipt->number}",
                ]);
                $this->journals->recordStockMovement($movement, $userId);
                $movement->lotAllocations()->create([
                    'inventory_lot_id' => $lot->id,
                    'quantity' => $quantity,
                    'unit_cost' => $item->unit_price,
                ]);
            }

            $oldStatus = $receipt->status;
            $receipt->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now()]);
            $this->auditLog->log(
                action: 'APPROVE',
                module: 'GOODS_RECEIPT',
                description: "Menyetujui penerimaan {$receipt->number}; stok dan jurnal diposting",
                model: $receipt,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $receipt->status, 'approved_by' => $userId, 'approved_at' => $receipt->approved_at],
                userId: $userId,
            );

            return $receipt->load(['purchaseOrder', 'supplier', 'receiver', 'approver', 'items.product.unit']);
        });
    }
}

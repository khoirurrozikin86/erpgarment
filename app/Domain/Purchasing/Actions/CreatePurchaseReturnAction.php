<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Purchasing\DTOs\PurchaseReturnData;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseReturn;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePurchaseReturnAction
{
    public function __construct(
        private DocumentNumberService $numbers,
        private AuditLogService $auditLog,
        private AutomaticJournalService $journals,
    ) {}

    public function __invoke(PurchaseReturnData $data, int $userId): PurchaseReturn
    {
        return DB::transaction(function () use ($data, $userId) {
            $receipt = GoodsReceipt::query()->lockForUpdate()->findOrFail($data->goodsReceiptId);
            $receiptItems = GoodsReceiptItem::query()
                ->where('goods_receipt_id', $receipt->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $returnItems = collect($data->items)
                ->filter(fn (array $item) => (float) $item['quantity'] > 0)
                ->values();
            if ($returnItems->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Masukkan jumlah untuk minimal satu barang retur.']);
            }

            foreach ($returnItems as $item) {
                $receiptItem = $receiptItems->get((int) $item['goods_receipt_item_id']);
                if (!$receiptItem) {
                    throw ValidationException::withMessages(['items' => 'Barang yang dipilih tidak termasuk dalam penerimaan ini.']);
                }

                $available = round((float) $receiptItem->quantity - (float) $receiptItem->returned_quantity, 4);
                if ((float) $item['quantity'] > $available) {
                    throw ValidationException::withMessages([
                        'items' => "Jumlah retur melebihi jumlah yang masih dapat diretur untuk {$receipt->number}.",
                    ]);
                }

            }

            $requestedByProduct = $returnItems->groupBy(function (array $item) use ($receiptItems) {
                return $receiptItems->get((int) $item['goods_receipt_item_id'])->product_id;
            })->sortKeys();
            foreach ($requestedByProduct as $productId => $itemsForProduct) {
                $product = Product::query()->lockForUpdate()->findOrFail($productId);
                if (!$product->track_stock) {
                    continue;
                }

                $stock = ProductStock::query()->where('product_id', $product->id)->lockForUpdate()->first();
                $requestedQuantity = round($itemsForProduct->sum(fn (array $item) => (float) $item['quantity']), 4);
                if (!$stock || (float) $stock->quantity < $requestedQuantity) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$product->name} tidak cukup untuk diproses sebagai retur.",
                    ]);
                }
            }

            $purchaseReturn = PurchaseReturn::create([
                'number' => $this->numbers->generate('purchase_return'),
                'goods_receipt_id' => $receipt->id,
                'supplier_id' => $receipt->supplier_id,
                'returned_by' => $userId,
                'returned_at' => $data->returnedAt,
                'reason' => $data->reason,
                'notes' => $data->notes,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;
            foreach ($returnItems as $item) {
                $receiptItem = $receiptItems->get((int) $item['goods_receipt_item_id']);
                $product = Product::query()->lockForUpdate()->findOrFail($receiptItem->product_id);
                $quantity = round((float) $item['quantity'], 4);
                $lineTotal = round($quantity * (float) $receiptItem->unit_price, 2);

                $returnLine = $purchaseReturn->items()->create([
                    'goods_receipt_item_id' => $receiptItem->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $receiptItem->unit_price,
                    'line_total' => $lineTotal,
                ]);

                $receiptItem->returned_quantity = round((float) $receiptItem->returned_quantity + $quantity, 4);
                $receiptItem->save();
                $totalAmount += $lineTotal;

                if ($product->track_stock) {
                    $stock = ProductStock::query()->where('product_id', $product->id)->lockForUpdate()->firstOrFail();
                    $before = round((float) $stock->quantity, 4);
                    if ($before < $quantity) {
                        throw ValidationException::withMessages([
                            'items' => "Stok {$product->name} tidak cukup untuk diproses sebagai retur.",
                        ]);
                    }
                    $after = round($before - $quantity, 4);
                    $unitCost = (float) $stock->average_unit_cost > 0 ? (float) $stock->average_unit_cost : (float) $product->purchase_price;
                    $costAmount = round($quantity * $unitCost, 2);
                    $stock->update(['quantity' => $after]);

                    $movement = StockMovement::create([
                        'product_id' => $product->id,
                        'purchase_return_id' => $purchaseReturn->id,
                        'purchase_return_item_id' => $returnLine->id,
                        'created_by' => $userId,
                        'movement_type' => 'purchase_return',
                        'quantity' => -$quantity,
                        'quantity_before' => $before,
                        'quantity_after' => $after,
                        'cost_amount' => $costAmount,
                        'notes' => "Retur pembelian {$purchaseReturn->number}",
                    ]);
                    $this->journals->recordStockMovement($movement, $userId, $lineTotal);
                } else {
                    $this->journals->recordNonStockPurchaseReturn($returnLine, $receipt->number, $purchaseReturn->number, $purchaseReturn->returned_at, $userId);
                }
            }

            $purchaseReturn->update(['total_amount' => round($totalAmount, 2)]);

            $this->auditLog->log(
                action: 'RETURN',
                module: 'PURCHASE_RETURN',
                description: "Mencatat retur pembelian {$purchaseReturn->number} untuk penerimaan {$receipt->number}",
                model: $purchaseReturn,
                oldValues: null,
                newValues: $purchaseReturn->load('items')->toArray(),
            );

            return $purchaseReturn->load([
                'goodsReceipt.purchaseOrder', 'supplier', 'returner', 'items.product.unit',
            ]);
        });
    }
}

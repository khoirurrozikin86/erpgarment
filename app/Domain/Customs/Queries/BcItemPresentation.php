<?php

namespace App\Domain\Customs\Queries;

use App\Models\GoodsReceiptItem;
use App\Models\GoodsSentNoteItem;
use App\Models\Product;
use Illuminate\Support\Collection;

class BcItemPresentation
{
    public static function product(Product $product): array
    {
        return [
            'id' => $product->id,
            'code' => $product->code,
            'parent_code' => $product->parentProduct?->code ?? $product->code,
            'sku_code' => $product->parent_product_id ? $product->code : null,
            'name' => $product->name,
            'color' => $product->color,
            'size' => $product->size,
            'unit' => $product->unit?->symbol ?: $product->unit?->name,
        ];
    }

    public static function products(array $lines): Collection
    {
        return Product::with(['unit', 'parentProduct:id,code'])
            ->whereIn('id', collect($lines)->pluck('product_id')->filter())
            ->get()->mapWithKeys(fn (Product $product) => [$product->id => self::product($product)]);
    }

    public static function lines(array $lines, int $companyId, ?int $supplierId): array
    {
        $receipts = GoodsReceiptItem::with(['product', 'purchaseOrderItem.purchaseRequestItem.materialRequestItem'])
            ->whereIn('id', collect($lines)->pluck('goods_receipt_item_id')->filter())
            ->whereHas('goodsReceipt', fn ($receipt) => $receipt->where('status', 'approved')
                ->when($supplierId, fn ($query) => $query->where('supplier_id', $supplierId)))
            ->get()->keyBy('id');

        $shipments = GoodsSentNoteItem::with('productionOrder:id,order_color,order_size')
            ->whereIn('id', collect($lines)->pluck('goods_sent_note_item_id')->filter())
            ->whereHas('note', fn ($note) => $note->where('company_id', $companyId)->whereNotNull('approved_at'))
            ->get()->keyBy('id');

        return array_map(function (array $line) use ($receipts, $shipments) {
            $receipt = $receipts->get($line['goods_receipt_item_id'] ?? 0);
            $shipment = $shipments->get($line['goods_sent_note_item_id'] ?? 0);
            $productId = (int) ($line['product_id'] ?? 0);

            if ($receipt && (int) $receipt->product_id === $productId) {
                $line['color'] = $receipt->received_color;
                $line['size'] = $receipt->received_size;
            }
            if ($shipment && (int) $shipment->product_id === $productId) {
                $line['color'] = $shipment->productionOrder?->order_color ?? $line['color'] ?? null;
                $line['size'] = $shipment->productionOrder?->order_size ?? $line['size'] ?? null;
            }
            return $line;
        }, $lines);
    }
}

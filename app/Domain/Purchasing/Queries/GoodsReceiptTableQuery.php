<?php

namespace App\Domain\Purchasing\Queries;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class GoodsReceiptTableQuery
{
    use AppliesDateRange;

    public function builder(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = GoodsReceipt::query()
            ->with([
                'purchaseOrder:id,number,purchase_request_id',
                'purchaseOrder.purchaseRequest:id,material_request_id',
                'purchaseOrder.purchaseRequest.materialRequest:id,number,production_order_id',
                'purchaseOrder.purchaseRequest.materialRequest.productionOrder:id,number',
                'supplier:id,name',
                'receiver:id,name',
                'approver:id,name',
            ])
            ->withCount('items')
            ->orderByDesc('received_at');

        return $this->applyDateRange($query, 'received_at', $fromDate, $toDate);
    }

    public function itemRowsBuilder(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = GoodsReceiptItem::query()
            ->with([
                'goodsReceipt:id,number,purchase_order_id,supplier_id,received_at,invoice_number,invoice_date,currency,notes,status',
                'goodsReceipt.supplier:id,name',
                'goodsReceipt.purchaseOrder:id,number,purchase_request_id',
                'goodsReceipt.purchaseOrder.purchaseRequest:id,material_request_id',
                'goodsReceipt.purchaseOrder.purchaseRequest.materialRequest:id,number,production_order_id,output_product_id,production_quantity',
                'goodsReceipt.purchaseOrder.purchaseRequest.materialRequest.productionOrder:id,number,output_product_id,planned_quantity',
                'goodsReceipt.purchaseOrder.purchaseRequest.materialRequest.productionOrder.outputProduct:id,style_code',
                'goodsReceipt.purchaseOrder.purchaseRequest.materialRequest.outputProduct:id,style_code',
                'goodsReceipt.approver:id,name',
                'product:id,code,name,unit_id,style_code',
                'product.unit:id,name,symbol',
            ])
            ->select([
                'goods_receipt_items.id',
                'goods_receipt_items.goods_receipt_id',
                'goods_receipt_items.purchase_order_item_id',
                'goods_receipt_items.product_id',
                'goods_receipt_items.inventory_lot_id',
                'goods_receipt_items.quantity',
                'goods_receipt_items.unit_price',
                'goods_receipt_items.line_total',
            ])
            ->orderByDesc('goods_receipt_items.goods_receipt_id')
            ->orderBy('goods_receipt_items.id');

        if ($fromDate) {
            $query->whereHas('goodsReceipt', fn (Builder $receipt) => $receipt->where('received_at', '>=', CarbonImmutable::parse($fromDate)->startOfDay()));
        }
        if ($toDate) {
            $query->whereHas('goodsReceipt', fn (Builder $receipt) => $receipt->where('received_at', '<', CarbonImmutable::parse($toDate)->addDay()->startOfDay()));
        }

        return $query;
    }

    public function eligiblePurchaseOrders(): Builder
    {
        return PurchaseOrder::query()
            ->whereIn('status', ['issued', 'partially_received'])
            ->whereHas('items', fn (Builder $items) => $items->whereColumn('received_quantity', '<', 'quantity'))
            ->with([
                'supplier:id,name',
                'purchaseRequest.materialRequest.productionOrder.outputProduct:id,style_code',
                'purchaseRequest.materialRequest:id,production_order_id,output_product_id',
                'purchaseRequest.materialRequest.productionOrder:id,number,output_product_id,currency',
                'items.product:id,code,name,unit_id,track_stock',
                'items.product.unit:id,name,symbol',
            ])
            ->withCount('items')
            ->orderByDesc('issued_at');
    }

    public function sourcePurchaseOrder(int $id): PurchaseOrder
    {
        return $this->eligiblePurchaseOrders()->findOrFail($id);
    }

    public function details(GoodsReceipt $receipt): GoodsReceipt
    {
        return GoodsReceipt::query()
            ->with([
                'purchaseOrder:id,number,status,purchase_request_id', 'supplier', 'receiver:id,name', 'approver:id,name',
                'purchaseOrder.purchaseRequest:id,material_request_id',
                'purchaseOrder.purchaseRequest.materialRequest:id,production_order_id,output_product_id',
                'purchaseOrder.purchaseRequest.materialRequest.productionOrder:id,number,output_product_id,planned_quantity',
                'purchaseOrder.purchaseRequest.materialRequest.productionOrder.outputProduct:id,style_code',
                'purchaseOrder.purchaseRequest.materialRequest.outputProduct:id,style_code',
                'items.product:id,code,name,unit_id,track_stock', 'items.product.unit:id,name,symbol', 'items.inventoryLot:id,lot_number',
            ])
            ->findOrFail($receipt->id);
    }
}

<?php

namespace App\Domain\Customs\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BcDocumentVariantQuery
{
    public static function rows(Builder $documents, ?string $search, string $reference, string $party): Builder
    {
        $documents
            ->leftJoin('products as product', 'product.id', '=', 'i.product_id')
            ->leftJoin('products as parent', 'parent.id', '=', 'product.parent_product_id')
            ->leftJoin('goods_receipt_items as receipt', 'receipt.id', '=', 'i.goods_receipt_item_id')
            ->leftJoin('purchase_order_items as po', 'po.id', '=', 'receipt.purchase_order_item_id')
            ->leftJoin('purchase_request_items as pr', 'pr.id', '=', 'po.purchase_request_item_id')
            ->leftJoin('material_request_items as mr', 'mr.id', '=', 'pr.material_request_item_id')
            ->leftJoin('goods_sent_note_items as shipment', 'shipment.id', '=', 'i.goods_sent_note_item_id')
            ->leftJoin('production_orders as mo', 'mo.id', '=', 'shipment.production_order_id')
            ->addSelect('i.id as item_id')
            ->selectRaw('COALESCE(parent.code, i.item_code, product.code) as parent_code')
            ->selectRaw('CASE WHEN product.parent_product_id IS NOT NULL THEN COALESCE(i.item_code, product.code) END as sku_code')
            ->selectRaw("COALESCE(NULLIF(TRIM(mo.order_color), ''), NULLIF(TRIM(mr.color), ''), product.color) as color")
            ->selectRaw("COALESCE(NULLIF(TRIM(mo.order_size), ''), NULLIF(TRIM(mr.size), ''), product.size) as size");

        return DB::query()->fromSub($documents, 'bc_document_rows')
            ->when($search, function (Builder $query, string $search) use ($reference, $party) {
                $query->where(function (Builder $rows) use ($search, $reference, $party) {
                    foreach (['parent_code', 'sku_code', 'item_name', 'color', 'size', 'submission_number', 'document_number', $reference, $party] as $field) {
                        $rows->orWhere($field, 'like', "%{$search}%");
                    }
                });
            })->orderByDesc('document_id')->orderBy('item_id');
    }
}

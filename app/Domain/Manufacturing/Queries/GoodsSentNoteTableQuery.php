<?php

namespace App\Domain\Manufacturing\Queries;

use App\Models\{GoodsSentNote, ProductionOrder};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GoodsSentNoteTableQuery
{
    public function builder(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return GoodsSentNote::query()->where('company_id', $companyId)
            ->when($from, fn ($q) => $q->whereDate('sent_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('sent_date', '<=', $to))
            ->with(['creator:id,name,username', 'items.productionOrder:id,number,buyer_po_number,customer_id', 'items.productionOrder.buyer:id,name', 'items.customer:id,name'])
            ->latest('sent_date')->latest('id');
    }

    public function eligibleOrders(int $companyId): Builder
    {
        $finished = DB::table('production_order_stage_entries')->selectRaw('production_order_id,SUM(good_quantity) as finished_qty')
            ->where('stage', 'finishing')->whereNull('deleted_at')->groupBy('production_order_id');
        $sent = DB::table('goods_sent_note_items')->join('goods_sent_notes', 'goods_sent_notes.id', '=', 'goods_sent_note_items.goods_sent_note_id')
            ->selectRaw('goods_sent_note_items.production_order_id,SUM(goods_sent_note_items.quantity) as sent_qty')
            ->whereNull('goods_sent_note_items.deleted_at')->whereNull('goods_sent_notes.deleted_at')->groupBy('goods_sent_note_items.production_order_id');
        return ProductionOrder::query()->where('production_orders.company_id', $companyId)
            ->joinSub($finished, 'finished', fn ($j) => $j->on('finished.production_order_id', '=', 'production_orders.id'))
            ->leftJoinSub($sent, 'sent', fn ($j) => $j->on('sent.production_order_id', '=', 'production_orders.id'))
            ->whereRaw('COALESCE(finished.finished_qty,0)>COALESCE(sent.sent_qty,0)')
            ->with(['outputProduct:id,code,name,style_code,unit_id,track_stock', 'outputProduct.unit:id,name,symbol', 'buyer:id,name'])
            ->select(['production_orders.id', 'production_orders.company_id', 'production_orders.number', 'production_orders.buyer_po_number', 'production_orders.customer_id', 'production_orders.output_product_id'])
            ->selectRaw('COALESCE(finished.finished_qty,0)-COALESCE(sent.sent_qty,0) as available_quantity')->latest('production_orders.created_at');
    }

    public function approvals(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return GoodsSentNote::query()->where('company_id', $companyId)
            ->when($from, fn ($q) => $q->whereDate('sent_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('sent_date', '<=', $to))
            ->with(['approver:id,name,username', 'items.productionOrder:id,number,buyer_po_number,customer_id', 'items.productionOrder.buyer:id,name', 'items.customer:id,name', 'items.productionOrder.outputProduct:id,code,name,unit_id,style_code', 'items.productionOrder.outputProduct.unit:id,name,symbol'])
            ->latest('sent_date')->latest('id');
    }
}

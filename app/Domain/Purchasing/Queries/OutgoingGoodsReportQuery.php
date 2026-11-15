<?php

namespace App\Domain\Purchasing\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class OutgoingGoodsReportQuery
{
    public function builder(int $companyId, array $filters): Builder
    {
        $deliveries = DB::table('goods_sent_note_items as i')
            ->join('goods_sent_notes as d', 'd.id', '=', 'i.goods_sent_note_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'p.unit_id')
            ->leftJoin('customers as c', 'c.id', '=', 'i.customer_id')
            ->where('d.company_id', $companyId)->whereNull('d.deleted_at')->whereNull('i.deleted_at')->whereNotNull('d.approved_at')
            ->selectRaw("i.id, 'delivery' as source, d.number, d.sent_date as document_date, c.name as recipient, i.product_id, p.code as item_code, p.name as item_name, COALESCE(NULLIF(u.symbol, ''), u.name) as unit, i.quantity");
        $equipment = DB::table('equipment_issue_items as i')
            ->join('equipment_issue_documents as d', 'd.id', '=', 'i.equipment_issue_document_id')
            ->where('d.company_id', $companyId)->whereNull('d.deleted_at')
            ->selectRaw("i.id, 'equipment' as source, d.number, d.issue_date as document_date, NULL as recipient, i.product_id, i.item_code, i.item_name, i.unit, i.quantity");
        return DB::query()->fromSub($deliveries->unionAll($equipment), 'outgoing')
            ->where('document_date', '>=', $filters['from_date'])->where('document_date', '<=', $filters['to_date'])
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $q) use ($search) {
                    foreach (['number', 'recipient', 'item_code', 'item_name'] as $column) $q->orWhere($column, 'like', "%{$search}%");
                });
            })->orderBy('document_date')->orderBy('source')->orderBy('id');
    }
}

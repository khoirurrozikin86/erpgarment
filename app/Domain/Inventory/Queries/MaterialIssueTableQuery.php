<?php

namespace App\Domain\Inventory\Queries;

use App\Models\MaterialIssueItem;
use App\Models\RawMaterialOrderDocument;
use Illuminate\Database\Eloquent\Builder;

class MaterialIssueTableQuery
{
    public function productionRequests(int $companyId): Builder
    {
        return RawMaterialOrderDocument::query()
            ->where('company_id', $companyId)
            ->with([
                'items:id,raw_material_order_document_id,production_order_material_id,product_id,quantity,issued_quantity',
                'items.sourceMaterial.productionOrder:id,number',
                'items.latestMaterialIssueItem.document:id,number,issue_date',
            ])
            ->orderByDesc('order_date')->orderByDesc('id');
    }

    public function history(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return MaterialIssueItem::query()
            ->with([
                'document:id,company_id,material_request_id,raw_material_order_document_id,number,issue_date',
                'document.request:id,number,production_order_id,created_at',
                'document.request.productionOrder:id,number',
                'document.rawMaterialOrder:id,number,production_order_number',
                'rawMaterialOrderItem:id,number,raw_material_order_document_id,production_order_material_id,product_id',
                'rawMaterialOrderItem.sourceMaterial.productionOrder:id,number',
                'requestItem:id,material_request_id,quantity,issued_quantity',
                'product:id,code,name,unit_id', 'product.unit:id,name,symbol',
            ])
            ->whereHas('document', fn (Builder $q) => $q->where('company_id', $companyId)
                ->when($from, fn (Builder $q) => $q->whereDate('issue_date', '>=', $from))
                ->when($to, fn (Builder $q) => $q->whereDate('issue_date', '<=', $to)))
            ->select('material_issue_items.*')
            ->latest('material_issue_items.created_at');
    }
}

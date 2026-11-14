<?php

namespace App\Domain\Inventory\Queries;

use App\Models\{ProductionReturnDocument, ProductionReturnItem};
use Illuminate\Database\Eloquent\Builder;

class ProductionReturnTableQuery
{
    public function items(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return ProductionReturnItem::query()->whereHas('document', fn (Builder $q) => $q->where('company_id',$companyId)->when($from,fn($q)=>$q->whereDate('return_date','>=',$from))->when($to,fn($q)=>$q->whereDate('return_date','<=',$to)))
            ->with(['document:id,company_id,number,return_date,raw_material_order_document_id,po_supplier_number,status','document.rawMaterialOrder:id,number','product:id,code,name,unit_id,style_code','product.unit:id,name,symbol'])
            ->select('production_return_items.*')->latest('production_return_items.created_at');
    }

    public function documents(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return ProductionReturnDocument::query()->where('company_id',$companyId)->when($from,fn($q)=>$q->whereDate('return_date','>=',$from))->when($to,fn($q)=>$q->whereDate('return_date','<=',$to))
            ->with(['rawMaterialOrder:id,number','items.product:id,code,name,unit_id,style_code','items.product.unit:id,name,symbol','approver:id,name'])->withSum('items','quantity')->latest('return_date')->latest('id');
    }
}

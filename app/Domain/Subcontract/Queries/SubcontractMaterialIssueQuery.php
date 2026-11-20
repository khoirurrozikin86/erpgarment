<?php
namespace App\Domain\Subcontract\Queries;

use App\Models\{Product, SubcontractMaterialIssueItem};
use Illuminate\Database\Eloquent\Builder;

class SubcontractMaterialIssueQuery
{
    protected string $issueKind = 'raw';
    public function builder(int $companyId, array $filters): Builder
    {
        return SubcontractMaterialIssueItem::with(['document.contract','document.receiver','document.confirmer','product.unit', 'product.parentProduct:id,code', 'product.category:id,name'])
            ->whereHas('document',fn(Builder $q)=>$q->where('company_id',$companyId)->where('issue_kind',$this->issueKind)->where('issue_date','>=',$filters['from_date'])->where('issue_date','<=',$filters['to_date']))
            ->when($filters['search']??null,function(Builder $q,string $search){
                $q->where(function(Builder $q)use($search){
                    $q->where('style','like',"%{$search}%")
                        ->orWhereHas('product',fn(Builder $q)=>$q->where('code','like',"%{$search}%")->orWhere('name','like',"%{$search}%"))
                        ->orWhereHas('document',fn(Builder $q)=>$q->where('number','like',"%{$search}%"))
                        ->orWhereHas('document.contract',fn(Builder $q)=>$q->where('contract_number','like',"%{$search}%"))
                        ->orWhereHas('document.receiver',fn(Builder $q)=>$q->where('name','like',"%{$search}%"));
                });
            })->orderByDesc('document_id')->orderBy('id');
    }
    public function products(): Builder
    {
        return Product::where('product_type','stock')->where('track_stock',true)->where('is_active',true)
            ->where(fn(Builder $q)=>$q->where('garment_item_type','fabric')->orWhereHas('category',fn(Builder $q)=>$q->whereIn('code',['RAW','FAB'])))
            ->with(['unit', ...SubcontractProductColumns::RELATIONS])->orderBy('code');
    }
}

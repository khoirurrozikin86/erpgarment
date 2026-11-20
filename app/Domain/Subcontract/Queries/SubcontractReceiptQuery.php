<?php
namespace App\Domain\Subcontract\Queries;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class SubcontractReceiptQuery extends SubcontractMaterialIssueQuery
{
    protected string $issueKind = 'receipt';
    public function products(): Builder
    {
        return Product::where('product_type','stock')->where('track_stock',true)->where('is_active',true)->with(['unit', ...SubcontractProductColumns::RELATIONS])->orderBy('code');
    }
    public function builder(int $companyId,array $filters): Builder
    {
        // Include invoice searches inside the same company/date/type restriction.
        if(empty($filters['search']))return parent::builder($companyId,$filters);
        $search=$filters['search'];unset($filters['search']);
        return parent::builder($companyId,$filters)->where(function(Builder $q)use($search){
            $q->where('style','like',"%{$search}%")
                ->orWhereHas('product',fn(Builder $q)=>$q->where('code','like',"%{$search}%")->orWhere('name','like',"%{$search}%"))
                ->orWhereHas('document',fn(Builder $q)=>$q->where('number','like',"%{$search}%")->orWhere('invoice_number','like',"%{$search}%")->orWhere('contract_number','like',"%{$search}%"))
                ->orWhereHas('document.contract',fn(Builder $q)=>$q->where('contract_number','like',"%{$search}%"))
                ->orWhereHas('document.receiver',fn(Builder $q)=>$q->where('name','like',"%{$search}%"));
        });
    }
}

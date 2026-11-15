<?php
namespace App\Domain\Subcontract\Queries;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class SubcontractWipIssueQuery extends SubcontractMaterialIssueQuery
{
    protected string $issueKind = 'wip';
    public function products(): Builder
    {
        return Product::where('product_type','stock')->where('track_stock',true)->where('is_active',true)
            ->where(fn(Builder $q)=>$q->where('garment_item_type','work_in_process')->orWhereHas('category',fn(Builder $q)=>$q->where('code','WIP')->orWhereRaw('LOWER(name) = ?', ['barang setengah jadi'])))
            ->with('unit')->orderBy('code');
    }
}

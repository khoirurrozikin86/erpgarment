<?php
namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Subcontract\Queries\SubcontractTableQuery;
use App\Models\{SubcontractContract,SubcontractMaterialIssueItem};
use Illuminate\Http\Request;

class SubcontractGoodsMonitoringController extends SubcontractContractMonitoringController
{
    public function index(Request $request,CompanyContext $company,SubcontractTableQuery $query)
    {
        $filters=$this->filters($request);
        $selection=$request->validate(['contract_id'=>['nullable','integer','min:1'],'direction'=>['nullable','in:receipt,issue']]);
        $contracts=$this->contracts($query,$company,$filters)->paginate($filters['per_page']??10)->withQueryString();
        $selected=null;$details=null;$direction=$selection['direction']??'issue';$ambiguous=false;
        if(!empty($selection['contract_id'])){
            $selected=SubcontractContract::where('company_id',$company->id())->with('subcontractor')->findOrFail($selection['contract_id']);
            // Manual receipt contract numbers only identify a contract if the match is unambiguous.
            $ambiguous=SubcontractContract::where('company_id',$company->id())->where('subcontractor_id',$selected->subcontractor_id)->where('contract_number',$selected->contract_number)->count()!==1;
            $details=SubcontractMaterialIssueItem::query()->with('product.unit')
                ->whereHas('document',function($q)use($company,$selected,$direction,$filters,$ambiguous){
                    $q->where('company_id',$company->id())->where('status','confirmed')->where('subcontractor_id',$selected->subcontractor_id)->where('issue_date','<=',$filters['to_date']);
                    if($direction==='issue')$q->whereIn('issue_kind',['raw','wip'])->where('contract_id',$selected->id);
                    else $q->where('issue_kind','receipt')->where(function($q)use($selected,$ambiguous){
                        $q->where('contract_id',$selected->id);
                        if(!$ambiguous)$q->orWhere(fn($q)=>$q->whereNull('contract_id')->where('contract_number',$selected->contract_number));
                    });
                })->select(['product_id','style'])->selectRaw('SUM(quantity) as realized_quantity')
                ->groupBy('product_id','style')->orderBy('product_id')->orderBy('style')
                ->paginate(25,['*'],'detail_page')->withQueryString();
        }
        return view('super.subcontract.goods-monitoring',compact('contracts','filters','selected','details','direction','ambiguous'));
    }
}

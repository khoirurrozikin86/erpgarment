<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Queries\DashboardProductionOrdersQuery;
use App\Models\{CustomsDocument,GoodsSentNote,Product,MaterialRequest,PurchaseRequest,PurchaseOrder};
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, CompanyContext $company, DashboardProductionOrdersQuery $productionOrders)
    {
        $filters=$request->validate(['from_date'=>['nullable','date_format:Y-m-d'],'to_date'=>['nullable','date_format:Y-m-d','after_or_equal:from_date']]);
        $filters['from_date']??=now()->startOfMonth()->toDateString();
        $filters['to_date']??=now()->toDateString();
        if($filters['from_date']>$filters['to_date'])throw \Illuminate\Validation\ValidationException::withMessages(['to_date'=>__('dashboard_flow.invalid_range')]);
        $user=$request->user();$companyId=$company->has()?$company->id():null;
        $flow=[];
        foreach(config('dashboard_flow') as $group=>$links){
            $visible=array_values(array_filter($links,fn($link)=>$user->can($link['permission'])));
            if($visible)$flow[$group]=$visible;
        }
        $queues=[];
        $period=fn($q,$field)=>$q->whereDate($field,'>=',$filters['from_date'])->whereDate($field,'<=',$filters['to_date']);
        $scoped=fn($q)=>$q->where('company_id',$companyId??0);
        $bc=[];
        foreach(['in'=>['bc23'=>'BC 2.3','bc262'=>'BC 2.6.2','bc27'=>'BC 2.7','bc40'=>'BC 4.0','bc20'=>'BC 2.0'],'out'=>['bc30'=>'BC 3.0','bc261'=>'BC 2.6.1','bc27out'=>'BC 2.7 OUT','bc41'=>'BC 4.1','bc25'=>'BC 2.5']] as $direction=>$types){
            foreach($types as $key=>$type){if(!$user->can($key.'.view'))continue;
                $bc[$direction][]=['label'=>$type,'count'=>$period($scoped(CustomsDocument::query())->where('document_type',$type),'document_date')->count(),'route'=>'super.bc-'.$direction.'.'.$key.'.index'];
            }
        }
        foreach([['material-requests.view',MaterialRequest::class,'mr','super.material-requests.index'],['purchase-requests.view',PurchaseRequest::class,'pr','super.purchase-requests.index'],['purchase-orders.view',PurchaseOrder::class,'po','super.purchase-orders.index']] as [$permission,$model,$key,$route]){
            if($user->can($permission))$queues[]=['key'=>$key,'count'=>$model::whereIn('status',$key==='po'?['draft','issued','partially_received']:['pending'])->count(),'route'=>$route,'shared'=>true];
        }
        if($user->can('goods-sent-approvals.view'))$queues[]=['key'=>'approval','count'=>$scoped(GoodsSentNote::query())->whereNull('approved_at')->count(),'route'=>'super.delivery-note-approvals.index','shared'=>false];
        $master=[];
        if($user->can('products.view'))foreach(['fabric','accessory','work_in_process','finished_good'] as $type)$master[$type]=Product::where('garment_item_type',$type)->count();
        $productionMonitoring = $user->can('production-orders.view') && $companyId !== null
            ? $productionOrders->forPeriod($companyId, $filters)
            : null;

        return view('super.dashboard', compact('filters', 'flow', 'queues', 'bc', 'master', 'productionMonitoring') + [
            'activeCompany' => $company->has() ? $company->get()->name : null,
        ]);
    }
}

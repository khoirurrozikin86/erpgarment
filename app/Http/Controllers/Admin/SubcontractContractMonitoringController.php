<?php
namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Subcontract\Queries\SubcontractTableQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubcontractContractMonitoringController extends Controller
{
    protected function filters(Request $request): array
    {
        $filters=$request->validate([
            'from_date'=>['nullable','date_format:Y-m-d'],'to_date'=>['nullable','date_format:Y-m-d'],
            'search'=>['nullable','string','max:100'],'per_page'=>['nullable','integer','in:10,25,50,100'],
        ]);
        $filters['from_date']??=now()->startOfYear()->toDateString();
        $filters['to_date']??=now()->toDateString();
        if($filters['from_date']>$filters['to_date'])throw ValidationException::withMessages(['to_date'=>__('subcontract_contract_monitoring.invalid_range')]);
        return $filters;
    }
    protected function contracts(SubcontractTableQuery $query, CompanyContext $company, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        return $query->contracts($company->id(),$filters['from_date'],$filters['to_date'])
            ->when($filters['search']??null,function($q,$search){
                $q->where(function($q)use($search){
                    $q->where('contract_number','like',"%{$search}%")->orWhere('bc_decree_number','like',"%{$search}%")->orWhere('description','like',"%{$search}%")
                        ->orWhereHas('subcontractor',fn($q)=>$q->where('name','like',"%{$search}%"));
                });
            })->orderByDesc('id');
    }
    public function index(Request $request, CompanyContext $company, SubcontractTableQuery $query)
    {
        $filters=$this->filters($request);
        $contracts=$this->contracts($query,$company,$filters)->paginate($filters['per_page']??10)->withQueryString();
        return view('super.subcontract.contract-monitoring',compact('filters','contracts'));
    }
}

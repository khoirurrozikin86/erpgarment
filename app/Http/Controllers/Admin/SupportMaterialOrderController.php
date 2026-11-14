<?php
namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Actions\SaveSupportMaterialOrderItemAction;
use App\Domain\Manufacturing\Queries\SupportMaterialOrderTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\SupportMaterialOrdersExport;
use App\Http\Controllers\Controller;
use App\Models\{Product, SupportMaterialOrderItem};
use Illuminate\Http\{JsonResponse, Request};
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class SupportMaterialOrderController extends Controller
{
    public function index() { return view('super.support-material-orders.index'); }
    public function numberPreview(DocumentNumberService $numbers): JsonResponse { return response()->json(['number' => $numbers->preview('support_material_order')]); }

    private function filters(Request $request): array
    {
        return $request->validate(['from_date'=>['nullable','date_format:Y-m-d'],'to_date'=>['nullable','date_format:Y-m-d',function($a,$v,$fail)use($request){if($v&&$request->from_date&&$v<$request->from_date)$fail(__('ui.support_material_order.invalid_range'));}]]);
    }

    public function export(Request $request, CompanyContext $company)
    {
        $f=$this->filters($request);
        return Excel::download(new SupportMaterialOrdersExport($company->id(),$f['from_date']??null,$f['to_date']??null),'support-material-orders.xlsx');
    }

    public function dt(Request $request, CompanyContext $company, SupportMaterialOrderTableQuery $query): JsonResponse
    {
        $f=$this->filters($request);
        return DataTables::eloquent($query->builder($company->id(),$f['from_date']??null,$f['to_date']??null))
            ->addIndexColumn()->addColumn('number',fn($r)=>$r->document?->number)
            ->addColumn('order_date',fn($r)=>$r->document?->order_date?->format('Y-m-d'))
            ->addColumn('code',fn($r)=>$r->product?->code)->addColumn('name',fn($r)=>$r->product?->name)
            ->addColumn('unit',fn($r)=>$r->product?->unit?->symbol?:$r->product?->unit?->name?:'—')
            ->addColumn('actions',function(SupportMaterialOrderItem $r){
                $a=''; if(auth()->user()->can('support-material-orders.update')) $a.='<li><button type="button" class="dropdown-item btn-edit-support-order" data-id="'.$r->id.'" data-product="'.$r->product_id.'" data-code="'.e($r->product?->code).'" data-name="'.e($r->product?->name).'" data-unit="'.e($r->product?->unit?->symbol?:$r->product?->unit?->name).'" data-date="'.$r->document?->order_date?->format('Y-m-d').'" data-quantity="'.e($r->quantity).'" data-style="'.e($r->style).'" data-po="'.e($r->po_other).'">'.e(__('ui.common.edit')).'</button></li>';
                if(auth()->user()->can('support-material-orders.delete')) $a.='<li><button type="button" class="dropdown-item text-danger btn-delete-support-order" data-id="'.$r->id.'">'.e(__('ui.common.delete')).'</button></li>';
                return $a?'<div class="dropdown"><button class="btn btn-sm border-0 bg-transparent p-0" data-bs-toggle="dropdown"><i data-feather="more-vertical" class="icon-sm"></i></button><ul class="dropdown-menu dropdown-menu-end">'.$a.'</ul></div>':'';
            })->rawColumns(['actions'])->toJson();
    }

    public function productsDt(SupportMaterialOrderTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->products())->addColumn('unit_label',fn(Product $p)=>$p->unit?->symbol?:$p->unit?->name?:'—')
            ->addColumn('actions',fn(Product $p)=>'<button type="button" class="btn btn-sm btn-primary btn-select-support-product" data-id="'.$p->id.'" data-code="'.e($p->code).'" data-name="'.e($p->name).'" data-unit="'.e($p->unit?->symbol?:$p->unit?->name).'">'.e(__('ui.common.select')).'</button>')->rawColumns(['actions'])->toJson();
    }

    public function store(Request $request, SaveSupportMaterialOrderItemAction $action): JsonResponse
    {
        $item=$action->create($this->validated($request),(int)$request->user()->id);
        return response()->json(['message'=>__('ui.support_material_order.saved'),'number'=>$item->document?->number,'document_id'=>$item->document_id],201);
    }
    public function update(Request $request, SupportMaterialOrderItem $item, CompanyContext $company, SaveSupportMaterialOrderItemAction $action): JsonResponse
    {
        abort_unless($item->document()->where('company_id',$company->id())->exists(),404);
        $action->update($item,$this->validated($request),(int)$request->user()->id); return response()->json(['message'=>__('ui.support_material_order.updated')]);
    }
    public function destroy(Request $request, SupportMaterialOrderItem $item, CompanyContext $company, SaveSupportMaterialOrderItemAction $action): JsonResponse
    {
        abort_unless($item->document()->where('company_id',$company->id())->exists(),404);
        $empty=$action->delete($item,(int)$request->user()->id); return response()->json(['message'=>__('ui.support_material_order.deleted'),'document_empty'=>$empty]);
    }
    private function validated(Request $request): array
    {
        return $request->validate(['document_id'=>['nullable','integer'],'order_date'=>['required','date_format:Y-m-d'],'product_id'=>['required','integer','exists:products,id'],'quantity'=>['required','numeric','gt:0','max:999999999999.9999'],'po_other'=>['nullable','string','max:100'],'style'=>['nullable','string','max:100']]);
    }
}

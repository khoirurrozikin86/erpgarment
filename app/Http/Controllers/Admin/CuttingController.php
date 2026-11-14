<?php
namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Actions\SaveCuttingAction;
use App\Domain\Manufacturing\Queries\CuttingTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\CuttingExport;
use App\Http\Controllers\Controller;
use App\Models\ProductionOrderStageEntry;
use Illuminate\Http\{JsonResponse,Request};
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class CuttingController extends Controller
{
 public function index(){return view('super.cutting.index');}
 public function preview(DocumentNumberService $numbers):JsonResponse{return response()->json(['number'=>$numbers->preview('cutting')]);}
 private function filters(Request $r):array{return $r->validate(['from_date'=>['nullable','date_format:Y-m-d'],'to_date'=>['nullable','date_format:Y-m-d',function($a,$v,$fail)use($r){if($v&&$r->from_date&&$v<$r->from_date)$fail(__('ui.cutting.invalid_range'));}]]);}
 public function dt(Request $r,CompanyContext $company,CuttingTableQuery $query):JsonResponse
 {
  $f=$this->filters($r);return DataTables::eloquent($query->builder($company->id(),$f['from_date']??null,$f['to_date']??null))->addIndexColumn()
   ->addColumn('number',fn($x)=>$x->cutting_number)->addColumn('date',fn($x)=>$x->processed_at?->format('Y-m-d'))
   ->addColumn('mo',fn($x)=>$x->productionOrder?->number)->addColumn('po_buyer',fn($x)=>$x->productionOrder?->buyer_po_number?:'—')
   ->addColumn('style',fn($x)=>$x->productionOrder?->outputProduct?->style_code?:'—')->addColumn('product_code',fn($x)=>$x->productionOrder?->outputProduct?->code)
   ->addColumn('product_name',fn($x)=>$x->productionOrder?->outputProduct?->name)->addColumn('unit',fn($x)=>$x->productionOrder?->outputProduct?->unit?->symbol?:$x->productionOrder?->outputProduct?->unit?->name?:'—')
   ->addColumn('material_code',fn($x)=>$x->materialProduct?->code)->addColumn('material_name',fn($x)=>$x->materialProduct?->name)
   ->addColumn('material_unit',fn($x)=>$x->materialProduct?->unit?->symbol?:$x->materialProduct?->unit?->name?:'—')
   ->addColumn('user',fn($x)=>$x->processor?->username?:$x->processor?->name?:'—')
   ->addColumn('actions',function(ProductionOrderStageEntry $x){
    $html='<button type="button" class="btn btn-primary btn-sm py-0 px-2 btn-show-cutting-detail">'.e(__('ui.common.view_detail')).'</button>';
    if(auth()->user()->can('cutting.update')) $html.='<button type="button" class="btn btn-outline-primary btn-sm p-1 btn-edit-cutting" title="'.e(__('ui.common.edit')).'" data-id="'.$x->id.'" data-number="'.e($x->cutting_number).'" data-mo="'.$x->production_order_id.'" data-mo-number="'.e($x->productionOrder?->number).'" data-product="'.e($x->productionOrder?->outputProduct?->code.' — '.$x->productionOrder?->outputProduct?->name).'" data-style="'.e($x->productionOrder?->outputProduct?->style_code).'" data-po="'.e($x->productionOrder?->buyer_po_number).'" data-unit="'.e($x->productionOrder?->outputProduct?->unit?->symbol?:$x->productionOrder?->outputProduct?->unit?->name).'" data-input="'.e($x->input_quantity).'" data-good="'.e($x->good_quantity).'" data-reject="'.e($x->rejected_quantity).'" data-material="'.$x->material_product_id.'" data-material-label="'.e($x->materialProduct?->code.' — '.$x->materialProduct?->name).'" data-material-unit="'.e($x->materialProduct?->unit?->symbol?:$x->materialProduct?->unit?->name).'" data-waste="'.e($x->material_waste_quantity).'" data-date="'.$x->processed_at?->format('Y-m-d').'" aria-label="'.e(__('ui.common.edit')).'"><i data-feather="edit-2" class="icon-sm"></i></button>';
    if(auth()->user()->can('cutting.delete')) $html.='<button type="button" class="btn btn-outline-danger btn-sm p-1 btn-delete-cutting" title="'.e(__('ui.common.delete')).'" data-id="'.$x->id.'" aria-label="'.e(__('ui.common.delete')).'"><i data-feather="trash-2" class="icon-sm"></i></button>';
    return '<div class="d-flex align-items-center gap-1">'.$html.'</div>';
   })->rawColumns(['actions'])->toJson();
 }
 public function ordersDt(CompanyContext $company,CuttingTableQuery $query):JsonResponse
 {return DataTables::eloquent($query->productionOrders($company->id()))->addColumn('style',fn($o)=>$o->outputProduct?->style_code?:'—')->addColumn('product_code',fn($o)=>$o->outputProduct?->code)->addColumn('product_name',fn($o)=>$o->outputProduct?->name)->addColumn('unit',fn($o)=>$o->outputProduct?->unit?->symbol?:$o->outputProduct?->unit?->name?:'—')->addColumn('actions',fn($o)=>'<button type="button" class="btn btn-sm btn-primary btn-select-cutting-mo" data-id="'.$o->id.'" data-number="'.e($o->number).'" data-product="'.e($o->outputProduct?->code.' — '.$o->outputProduct?->name).'" data-style="'.e($o->outputProduct?->style_code).'" data-po="'.e($o->buyer_po_number).'" data-unit="'.e($o->outputProduct?->unit?->symbol?:$o->outputProduct?->unit?->name).'" data-planned="'.e($o->planned_quantity).'">'.e(__('ui.common.select')).'</button>')->rawColumns(['actions'])->toJson();}
 public function materialsDt(Request $r,CompanyContext $company):JsonResponse
 {
  $materials=\App\Models\ProductionOrderMaterial::query()->whereRaw('1 = 0');
  if($r->filled('production_order_id')){
   $data=$r->validate(['production_order_id'=>['required','integer']]);
   $order=\App\Models\ProductionOrder::query()->where('company_id',$company->id())->where('status','in_progress')->findOrFail($data['production_order_id']);
   $materials=\App\Models\ProductionOrderMaterial::query()->where('production_order_id',$order->id);
  }
  return DataTables::eloquent($materials->with('product.unit')->select('production_order_materials.*'))
   ->addColumn('code',fn($x)=>$x->product?->code)->addColumn('name',fn($x)=>$x->product?->name)
   ->addColumn('unit',fn($x)=>$x->product?->unit?->symbol?:$x->product?->unit?->name?:'—')
   ->addColumn('actions',fn($x)=>'<button class="btn btn-sm btn-primary btn-select-cutting-material" data-id="'.$x->product_id.'" data-label="'.e($x->product?->code.' — '.$x->product?->name).'" data-unit="'.e($x->product?->unit?->symbol?:$x->product?->unit?->name).'">'.e(__('ui.common.select')).'</button>')
   ->rawColumns(['actions'])->toJson();
 }
 public function export(Request $r,CompanyContext $company){$f=$this->filters($r);return Excel::download(new CuttingExport($company->id(),$f['from_date']??null,$f['to_date']??null),'cutting.xlsx');}
 public function store(Request $r,SaveCuttingAction $action):JsonResponse{$action->create($this->validated($r),(int)$r->user()->id);return response()->json(['message'=>__('ui.cutting.saved')],201);}
 public function update(Request $r,ProductionOrderStageEntry $entry,CompanyContext $company,SaveCuttingAction $action):JsonResponse{abort_unless($entry->stage==='cutting'&&$entry->productionOrder()->where('company_id',$company->id())->exists(),404);$action->update($entry,$this->validated($r),(int)$r->user()->id);return response()->json(['message'=>__('ui.cutting.updated')]);}
 public function destroy(Request $r,ProductionOrderStageEntry $entry,CompanyContext $company,SaveCuttingAction $action):JsonResponse{abort_unless($entry->stage==='cutting'&&$entry->productionOrder()->where('company_id',$company->id())->exists(),404);$action->delete($entry,(int)$r->user()->id);return response()->json(['message'=>__('ui.cutting.deleted')]);}
 private function validated(Request $r):array{return $r->validate(['production_order_id'=>['required','integer','exists:production_orders,id'],'cutting_date'=>['required','date_format:Y-m-d'],'material_product_id'=>['required','integer','exists:products,id'],'input_quantity'=>['required','numeric','gt:0','max:999999999999.9999'],'send_to_sewing'=>['required','numeric','min:0','max:999999999999.9999'],'rejected_quantity'=>['required','numeric','min:0','max:999999999999.9999'],'material_waste_quantity'=>['required','numeric','min:0','max:999999999999.9999']]);}
}

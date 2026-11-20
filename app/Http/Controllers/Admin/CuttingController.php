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
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class CuttingController extends Controller
{
 public function index(){return view('super.cutting.index');}
 public function create(DocumentNumberService $numbers):\Illuminate\View\View
 {
  return view('super.cutting.create', ['cuttingNumber' => $numbers->preview('cutting')]);
 }
 public function edit(ProductionOrderStageEntry $entry,CompanyContext $company):\Illuminate\View\View
 {
  abort_unless($entry->stage==='cutting'&&$entry->productionOrder()->where('company_id',$company->id())->exists(),404);
  $entry->load(['productionOrder.outputProduct','productionOrder.outputProduct.unit','productionOrder.outputProduct.parentProduct','materialProduct.unit']);
  return view('super.cutting.edit',[
   'entry'=>$entry,
   'cuttingNumber'=>$entry->cutting_number,
  ]);
 }
 public function preview(DocumentNumberService $numbers):JsonResponse{return response()->json(['number'=>$numbers->preview('cutting')]);}
 private function filters(Request $r):array{return $r->validate(['from_date'=>['nullable','date_format:Y-m-d'],'to_date'=>['nullable','date_format:Y-m-d',function($a,$v,$fail)use($r){if($v&&$r->from_date&&$v<$r->from_date)$fail(__('ui.cutting.invalid_range'));}]]);}
 public function dt(Request $r,CompanyContext $company,CuttingTableQuery $query):JsonResponse
 {
  $f=$this->filters($r);return DataTables::eloquent($query->builder($company->id(),$f['from_date'] ?? now()->startOfMonth()->toDateString(),$f['to_date'] ?? now()->toDateString()))->addIndexColumn()
   ->addColumn('number',fn($x)=>$x->cutting_number)->addColumn('date',fn($x)=>$x->processed_at?->format('Y-m-d'))
   ->addColumn('mo',fn($x)=>$x->productionOrder?->number)->addColumn('po_buyer',fn($x)=>$x->productionOrder?->buyer_po_number?:'—')
   ->addColumn('style',fn($x)=>$x->productionOrder?->model_style?:'—')->addColumn('product_code',fn($x)=>$x->productionOrder?->stageProductCode('cutting'))
   ->addColumn('product_name',fn($x)=>$x->productionOrder?->outputProduct?->name)->addColumn('color', fn ($x) => $x->productionOrder?->outputProduct?->color ?: '—')->addColumn('size', fn ($x) => $x->productionOrder?->outputProduct?->size ?: '—')->addColumn('order_quantity', fn ($x) => $x->productionOrder?->planned_quantity ?? 0)->addColumn('unit', fn($row) => 'PCS')
   ->addColumn('cut_quantity', fn ($entry) => round((float) $entry->good_quantity + (float) $entry->rejected_quantity, 4))
   ->addColumn('material_code',fn($x)=>$x->materialProduct?->code)->addColumn('material_name',fn($x)=>$x->materialProduct?->name)
   ->addColumn('material_unit',fn($x)=>$x->materialProduct?->unit?->symbol?:$x->materialProduct?->unit?->name?:'—')
   ->addColumn('user',fn($x)=>$x->processor?->name?:'—')
   ->addColumn('actions',function(ProductionOrderStageEntry $x){
    $html='<button type="button" class="btn btn-primary btn-sm py-0 px-2 btn-show-cutting-detail">'.e(__('ui.common.view_detail')).'</button>';
    if(auth()->user()->can('cutting.update')) $html.='<a href="'.e(route('super.cutting.edit',$x)).'" class="btn btn-outline-primary btn-sm p-1" title="'.e(__('ui.common.edit')).'" aria-label="'.e(__('ui.common.edit')).'"><i data-feather="edit-2" class="icon-sm"></i></a>';
    if(auth()->user()->can('cutting.delete')) $html.='<button type="button" class="btn btn-outline-danger btn-sm p-1 btn-delete-cutting" title="'.e(__('ui.common.delete')).'" data-id="'.$x->id.'" aria-label="'.e(__('ui.common.delete')).'"><i data-feather="trash-2" class="icon-sm"></i></button>';
    return '<div class="d-flex align-items-center gap-1">'.$html.'</div>';
   })->rawColumns(['actions'])->toJson();
 }
 public function ordersDt(CompanyContext $company,CuttingTableQuery $query):JsonResponse
 {
  return DataTables::eloquent($query->productionOrders($company->id()))
            ->addColumn('parent_code', fn ($o) => $o->outputProduct?->parentProduct?->code ?? $o->outputProduct?->code ?? '—')
            ->addColumn('sku_code', fn ($o) => $o->outputProduct?->parent_product_id ? $o->outputProduct->code : '—')
            ->addColumn('category_label', fn ($o) => $o->outputProduct?->category?->name ?? '—')
            ->addColumn('style',fn($o)=>$o->model_style?:'—')
   ->addColumn('product_code',fn($o)=>$o->outputProduct?->parentProduct?->code ?: $o->outputProduct?->code ?: '—')
   ->addColumn('product_name',fn($o)=>$o->outputProduct?->name ?: '—')
   ->addColumn('color', fn ($o) => $o->outputProduct?->color ?: '—')
   ->addColumn('size', fn ($o) => $o->outputProduct?->size ?: '—')
   ->addColumn('order_quantity', fn ($o) => $o->planned_quantity ?? 0)
   ->addColumn('unit', fn($row) => 'PCS')
   ->addColumn('mo_status',fn($o)=>match ($o->status) {
      'in_progress' => __('ui.workflow.production_status_in_progress'),
      'released' => __('ui.workflow.production_status_released'),
      'completed' => __('ui.workflow.production_status_completed'),
      'cancelled' => __('ui.workflow.production_status_cancelled'),
      default => $o->status ?: '—',
   })
   ->addColumn('variant_details', function ($o) use ($company) {
    $variants = $this->fabricVariantQuantities($company->id(), $o->number);
    return $variants->map(function ($variant) use ($o) {
     $code = e($o->outputProduct?->parentProduct?->code ?: $o->outputProduct?->code ?: '—');
     $name = e($o->outputProduct?->name ?: '—');
     $style = e($o->model_style ?: '—');
     $color = e($variant['color'] ?: '—');
     $size = e($variant['size'] ?: '—');
     $unit = e($o->outputProduct?->unit?->symbol ?: $o->outputProduct?->unit?->name ?: '—');
     $qty = \App\Support\DisplayNumber::format((float)$variant['quantity'], 4);
     return '<span class="d-block text-nowrap">'.$code.' — '.$name.' / '.$style.' / '.$color.' / '.$size.' / '.$qty.' '.$unit.'</span>';
    })->implode('');
   })
   ->addColumn('total_quantity', function ($o) use ($company) {
    $total = $this->fabricVariantQuantities($company->id(), $o->number)->sum('quantity');
    return \App\Support\DisplayNumber::format((float)$total, 4);
   })
   ->addColumn('actions', function ($o) use ($company) {
    $variantPayload = $this->fabricVariantQuantities($company->id(), $o->number)->values();
    $total = (float)$variantPayload->sum('quantity');
    $payload = e(json_encode($variantPayload, JSON_UNESCAPED_UNICODE));
    return '<button type="button" class="btn btn-sm btn-primary btn-select-cutting-mo" data-id="'.$o->id.'" data-number="'.e($o->number).'" data-product="'.e(($o->outputProduct?->parentProduct?->code ?: $o->outputProduct?->code).' — '.$o->outputProduct?->name).'" data-style="'.e($o->model_style).'" data-po="'.e($o->buyer_po_number).'" data-unit="PCS" data-planned="'.e($total).'" data-variants="'.$payload.'">'.e(__('ui.common.select')).'</button>';
   })
   ->rawColumns(['variant_details','mo_status','actions'])->toJson();
 }
 public function materialsDt(Request $r,CompanyContext $company):JsonResponse
 {
  $materials=collect();
  $order=null;
  if($r->filled('production_order_id')){
   $data=$r->validate([
    'production_order_id'=>['required','integer'],
    'production_order_number'=>['nullable','string','max:100'],
   ]);
   $order=\App\Models\ProductionOrder::query()->where('company_id',$company->id())->whereIn('status',['released','in_progress'])->findOrFail($data['production_order_id']);
   $orderIds=collect([$order->id]);
   $materials=\App\Models\ProductionOrderMaterial::query()
    ->whereIn('production_order_id',$orderIds)
    ->where(fn ($query) => $query
      ->whereHas('billOfMaterial', fn ($bom) => $bom->where('bom_type','fabric'))
      ->orWhereHas('product', fn ($product) => $product->where('garment_item_type','fabric')))
    ->with('product.unit')
    ->get()
    ->unique(fn (\App\Models\ProductionOrderMaterial $material) => $material->product_id)
    ->values();
    $materials=$materials->map(function ($material) use ($order) {
     $required=(float)$order->materials()->where('product_id',$material->product_id)->sum('required_quantity');
     $material->setAttribute('fabric_total_quantity',$required);
     return $material;
    });
  }
  return DataTables::collection($materials)
   ->addColumn('code',fn($x)=>$x->product?->code)->addColumn('name',fn($x)=>$x->product?->name)
   ->addColumn('unit',fn($x)=>$x->product?->unit?->symbol?:$x->product?->unit?->name?:'—')
   ->addColumn('fabric_total_quantity',fn($x)=>\App\Support\DisplayNumber::format((float)$x->fabric_total_quantity, 4))
   ->addColumn('actions',fn($x)=>'<button class="btn btn-sm btn-primary btn-select-cutting-material" data-id="'.$x->product_id.'" data-label="'.e($x->product?->code.' — '.$x->product?->name).'" data-unit="'.e($x->product?->unit?->symbol?:$x->product?->unit?->name).'" data-total="'.e($x->fabric_total_quantity).'" data-color-quantities="'.e(json_encode($order ? $this->fabricVariantQuantities($company->id(),$order->number,(int)$x->product_id) : [],JSON_UNESCAPED_UNICODE)).'">'.e(__('ui.common.select')).'</button>')
   ->rawColumns(['actions'])->toJson();
 }
 private function fabricVariantQuantities(int $companyId,string $number,?int $fabricProductId=null): Collection
 {
  $orders=\App\Models\ProductionOrder::query()
   ->where('company_id',$companyId)
   ->where('number',$number)
   ->whereIn('status',['released','in_progress'])
   ->with([
    'outputProduct:id,code,name,style_code,unit_id,color,size,parent_product_id',
    'outputProduct.parentProduct:id,code',
    'materials.billOfMaterial:id,bom_type',
    'materials.product:id,code,name,garment_item_type,unit_id',
    'materials.product.unit',
   ])
   ->get();

  return $orders->map(function ($order) use ($fabricProductId) {
   $fabric = $order->materials->filter(fn ($material) =>
    ($fabricProductId === null || (int)$material->product_id === $fabricProductId)
    && ($material->billOfMaterial?->bom_type === 'fabric' || $material->product?->garment_item_type === 'fabric'));
   $entries = $order->stageEntries()->where('stage','cutting')->get();
   return [
    'production_order_id'=>$order->id,
    'product_code'=>$order->stageProductCode('cutting'), 'product_name'=>$order->outputProduct?->name,
    'style'=>$order->model_style,
    'materials'=>$fabric->groupBy('product_id')->map(function ($rows, $productId) use ($entries) {
     $product=$rows->first()->product;
     return ['id'=>(int)$productId,'label'=>$product?->code.' — '.$product?->name,
      'unit'=>$product?->unit?->symbol ?: $product?->unit?->name,
      'remaining'=>max(0,(float)$rows->sum('required_quantity')-(float)$entries->where('material_product_id',(int)$productId)->sum('input_quantity'))];
    })->values(),
    'color'=>$order->outputProduct?->color ?: '—', 'size'=>$order->outputProduct?->size ?: '—',
    'quantity'=>(float)$order->planned_quantity,
    'remaining_quantity'=>max(0,(float)$order->planned_quantity - (float)$entries->sum('good_quantity') - (float)$entries->sum('rejected_quantity')),
    'fabric_remaining'=>max(0,(float)$fabric->sum('required_quantity') - (float)$entries->when($fabricProductId,fn($rows)=>$rows->where('material_product_id',$fabricProductId))->sum('input_quantity')),
   ];
  })->values();
 }

 public function export(Request $r,CompanyContext $company){$f=$this->filters($r);return Excel::download(new CuttingExport($company->id(),$f['from_date'] ?? now()->startOfMonth()->toDateString(),$f['to_date'] ?? now()->toDateString()),'cutting.xlsx');}
 public function store(Request $r,SaveCuttingAction $action):JsonResponse
 {
  if (!$r->has('lines')) {
   $action->create($this->validated($r),(int)$r->user()->id);
  } else {
   $data=$r->validate([
    'production_order_id'=>['required','integer'], 'cutting_date'=>['required','date_format:Y-m-d'],
    'lines.*.material_product_id'=>['nullable','integer','exists:products,id'],
    'lines'=>['required','array','min:1','max:500'],
    'lines.*.production_order_id'=>['required','integer','distinct'],
    'lines.*.input_quantity'=>['nullable','numeric','min:0','max:999999999999.9999'],
    'lines.*.send_to_sewing'=>['required','numeric','min:0','max:999999999999.9999'],
    'lines.*.rejected_quantity'=>['required','numeric','min:0','max:999999999999.9999'],
    'lines.*.material_waste_quantity'=>['nullable','numeric','min:0','max:999999999999.9999'],
   ]);
   $action->createBatch($data,(int)$r->user()->id);
  }
  return response()->json(['message'=>__('ui.cutting.saved')],201);
 }
 public function update(Request $r,ProductionOrderStageEntry $entry,CompanyContext $company,SaveCuttingAction $action):JsonResponse{abort_unless($entry->stage==='cutting'&&$entry->productionOrder()->where('company_id',$company->id())->exists(),404);$action->update($entry,$this->validated($r),(int)$r->user()->id);return response()->json(['message'=>__('ui.cutting.updated')]);}
 public function destroy(Request $r,ProductionOrderStageEntry $entry,CompanyContext $company,SaveCuttingAction $action):JsonResponse{abort_unless($entry->stage==='cutting'&&$entry->productionOrder()->where('company_id',$company->id())->exists(),404);$action->delete($entry,(int)$r->user()->id);return response()->json(['message'=>__('ui.cutting.deleted')]);}
 private function validated(Request $r):array{return $r->validate(['production_order_id'=>['required','integer','exists:production_orders,id'],'cutting_date'=>['required','date_format:Y-m-d'],'material_product_id'=>['nullable','integer','exists:products,id'],'input_quantity'=>['nullable','numeric','min:0','max:999999999999.9999'],'send_to_sewing'=>['required','numeric','min:0','max:999999999999.9999'],'rejected_quantity'=>['required','numeric','min:0','max:999999999999.9999'],'material_waste_quantity'=>['nullable','numeric','min:0','max:999999999999.9999']]);}
}

<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Actions\{ApproveProductionReturnAction, CreateProductionReturnAction, UpdateProductionReturnAction};
use App\Domain\Inventory\Queries\ProductionReturnTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\ProductionReturnExport;
use App\Http\Controllers\Controller;
use App\Models\{Product, ProductionReturnDocument, ProductionReturnItem, RawMaterialOrderDocument, ProductStock, StockMovement};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class ProductionReturnController extends Controller
{
    public function index(CompanyContext $company, ProductionReturnTableQuery $query): View
    {
        return view('super.production-returns.index', [
            'orders' => RawMaterialOrderDocument::query()->where('company_id',$company->id())->latest('id')->limit(500)->get(['id','number']),
            'products' => collect(),
        ]);
    }

    public function preview(DocumentNumberService $numbers): JsonResponse { return response()->json(['number'=>$numbers->preview('return_to_warehouse')]); }

    public function productsDt(): JsonResponse
    {
        return DataTables::eloquent(Product::query()->where('track_stock',true)->where('is_active',true)->with('unit:id,name,symbol')->select(['id','code','name','unit_id','style_code'])->orderBy('name'))
            ->addColumn('unit_label',fn(Product $p)=>$p->unit?->symbol ?: $p->unit?->name ?: '—')
            ->addColumn('actions',fn(Product $p)=>'<button type="button" class="btn btn-sm btn-primary btn-select-return-product" data-id="'.(int)$p->id.'" data-code="'.e($p->code).'" data-name="'.e($p->name).'" data-unit="'.e($p->unit?->symbol ?: $p->unit?->name ?: '').'" data-style="'.e($p->style_code).'" title="'.e(__('ui.common.select')).'"><i data-feather="check" class="icon-sm"></i></button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function dt(Request $request, CompanyContext $company, ProductionReturnTableQuery $query): JsonResponse
    {
        [$from,$to] = $this->dates($request);
        return DataTables::eloquent($query->items($company->id(),$from,$to))
            ->addColumn('number',fn(ProductionReturnItem $i)=>$i->document?->number)
            ->addColumn('return_date',fn(ProductionReturnItem $i)=>$i->document?->return_date?->format('Y-m-d'))
            ->addColumn('mo',fn(ProductionReturnItem $i)=>$i->document?->rawMaterialOrder?->number ?: '—')
            ->addColumn('po_supplier',fn(ProductionReturnItem $i)=>$i->document?->po_supplier_number ?: '—')
            ->addColumn('code',fn(ProductionReturnItem $i)=>$i->product?->code)
            ->addColumn('name',fn(ProductionReturnItem $i)=>$i->product?->name)
            ->addColumn('unit',fn(ProductionReturnItem $i)=>$i->product?->unit?->symbol ?: $i->product?->unit?->name ?: '—')
            ->addColumn('style',fn(ProductionReturnItem $i)=>$i->product?->style_code ?: '—')
            ->addColumn('actions',function(ProductionReturnItem $i){
                if($i->document?->status!=='pending') return '<span class="badge bg-secondary">'.e(__('ui.production_return.'.$i->document?->status)).'</span>';
                $buttons='';
                if(auth()->user()?->can('return-to-warehouse.update')) $buttons.='<button class="btn btn-link btn-sm p-0 text-primary btn-edit-production-return" data-url="'.e(route('super.return-to-warehouse.edit-data',$i->document)).'" data-update-url="'.e(route('super.return-to-warehouse.update',$i)).'" title="'.e(__('ui.common.edit')).'"><i data-feather="edit-2" class="icon-sm"></i></button>';
                if(auth()->user()?->can('return-to-warehouse.delete')) $buttons.='<button class="btn btn-link btn-sm p-0 text-danger btn-delete-production-return" data-url="'.e(route('super.return-to-warehouse.destroy',$i->document)).'" title="'.e(__('ui.common.delete')).'"><i data-feather="trash-2" class="icon-sm"></i></button>';
                return $buttons ? '<div class="d-inline-flex gap-2">'.$buttons.'</div>' : '<span class="text-muted">—</span>';
            })->rawColumns(['actions'])->toJson();
    }

    public function store(Request $request, CreateProductionReturnAction $action): JsonResponse
    {
        $data=$request->validate(['return_date'=>['required','date_format:Y-m-d'],'product_id'=>['required','integer','exists:products,id'],'quantity'=>['required','numeric','gt:0','max:999999999999.9999'],'raw_material_order_document_id'=>['nullable','integer','exists:raw_material_order_documents,id'],'po_supplier_number'=>['nullable','string','max:100'],'description'=>['nullable','string','max:1000']]);
        $document=$action($data,(int)$request->user()->id);
        return response()->json(['message'=>__('ui.production_return.saved'),'number'=>$document->number],201);
    }

    public function update(Request $request, ProductionReturnItem $item, CompanyContext $company, UpdateProductionReturnAction $action): JsonResponse
    {
        abort_unless($item->document()->where('company_id',$company->id())->exists(),404);
        $data=$request->validate(['return_date'=>['required','date_format:Y-m-d'],'product_id'=>['required','integer','exists:products,id'],'quantity'=>['required','numeric','gt:0','max:999999999999.9999'],'raw_material_order_document_id'=>['nullable','integer','exists:raw_material_order_documents,id'],'po_supplier_number'=>['nullable','string','max:100'],'description'=>['nullable','string','max:1000']]);
        $document=$action($item->document,$data,(int)$request->user()->id);
        return response()->json(['message'=>__('ui.production_return.updated'),'number'=>$document->number]);
    }

    public function destroy(ProductionReturnDocument $document, CompanyContext $company): JsonResponse
    {
        abort_unless($document->company_id===$company->id(),404);
        if($document->status!=='pending') throw ValidationException::withMessages(['document'=>__('ui.production_return.only_pending_delete')]);
        DB::transaction(function()use($document,$company){$locked=ProductionReturnDocument::query()->where('company_id',$company->id())->lockForUpdate()->findOrFail($document->id);$locked->items()->delete();$locked->delete();});
        return response()->json(['message'=>__('ui.production_return.deleted')]);
    }

    public function warehouseIndex(): View { return view('super.warehouse-production-receipts.index'); }

    public function warehouseDt(Request $request, CompanyContext $company, ProductionReturnTableQuery $query): JsonResponse
    {
        [$from,$to]=$this->dates($request);
        return DataTables::eloquent($query->documents($company->id(),$from,$to))
            ->addColumn('item_count',fn(ProductionReturnDocument $d)=>$d->items->count())
            ->addColumn('mo',fn(ProductionReturnDocument $d)=>$d->rawMaterialOrder?->number ?: '—')
            ->addColumn('approval_status',fn(ProductionReturnDocument $d)=>'<span class="badge bg-'.($d->status==='approved'?'success':($d->status==='cancelled'?'secondary':'danger')).'">'.e(__('ui.production_return.'.$d->status)).'</span>')
            ->addColumn('approver_name',fn(ProductionReturnDocument $d)=>$d->approver?->name ?: '—')
            ->editColumn('approved_at',fn(ProductionReturnDocument $d)=>$d->approved_at?->format('d-m-Y H:i') ?: '—')
            ->addColumn('actions',function(ProductionReturnDocument $d){$actions='<button class="btn btn-link btn-sm p-0 text-primary btn-show-production-return" data-url="'.e(route('super.warehouse-production-receipts.show',$d)).'" title="'.e(__('ui.common.detail')).'"><i data-feather="eye" class="icon-sm"></i></button>';if($d->status==='pending'&&auth()->user()?->can('warehouse-production-receipts.approve'))$actions.='<button class="btn btn-link btn-sm p-0 text-success btn-approve-production-return" data-url="'.e(route('super.warehouse-production-receipts.approve',$d)).'" data-number="'.e($d->number).'" title="'.e(__('ui.workflow.approve')).'"><i data-feather="check-circle" class="icon-sm"></i></button>';if($d->status==='approved'&&auth()->user()?->can('warehouse-production-receipts.cancel'))$actions.='<button class="btn btn-link btn-sm p-0 text-danger btn-cancel-production-return" data-url="'.e(route('super.warehouse-production-receipts.cancel',$d)).'" data-number="'.e($d->number).'" title="'.e(__('ui.common.cancel')).'"><i data-feather="x-circle" class="icon-sm"></i></button>';return '<div class="d-inline-flex gap-2">'.$actions.'</div>';})
            ->rawColumns(['approval_status','actions'])->toJson();
    }

    public function show(ProductionReturnDocument $document, CompanyContext $company): JsonResponse
    {
        $d=ProductionReturnDocument::query()->where('company_id',$company->id())->with(['rawMaterialOrder:id,number','items.product.unit','approver:id,name'])->findOrFail($document->id);
        return response()->json(['number'=>$d->number,'date'=>$d->return_date->format('Y-m-d'),'mo'=>$d->rawMaterialOrder?->number ?: '—','status'=>$d->status,'approver'=>$d->approver?->name,'approved_at'=>$d->approved_at?->format('d-m-Y H:i'),'items'=>$d->items->map(fn($i)=>['code'=>$i->product?->code,'name'=>$i->product?->name,'unit'=>$i->product?->unit?->symbol ?: $i->product?->unit?->name,'style'=>$i->product?->style_code,'quantity'=>(float)$i->quantity,'description'=>$i->description])]);
    }

    public function editDetails(ProductionReturnDocument $document, CompanyContext $company): JsonResponse
    {
        abort_unless($document->company_id===$company->id() && $document->status==='pending',404);
        return $this->show($document,$company);
    }

    public function approve(ProductionReturnDocument $document, CompanyContext $company, ApproveProductionReturnAction $action): JsonResponse
    {
        $approved=$action($document,$company->id(),(int)request()->user()->id);
        return response()->json(['message'=>__('ui.production_return.approved',['number'=>$approved->number])]);
    }

    public function cancel(ProductionReturnDocument $document, CompanyContext $company, Request $request, \App\Domain\Audit\Services\AuditLogService $audit): JsonResponse
    {
        DB::transaction(function()use($document,$company,$request,$audit){
            $d=ProductionReturnDocument::query()->where('company_id',$company->id())->with('items')->lockForUpdate()->findOrFail($document->id);
            if($d->status!=='approved') throw ValidationException::withMessages(['document'=>__('ui.production_return.not_approved')]);
            foreach($d->items as $item){$stock=ProductStock::query()->where('product_id',$item->product_id)->lockForUpdate()->firstOrFail();$before=(float)$stock->quantity;$qty=(float)$item->quantity;if($before<$qty)throw ValidationException::withMessages(['stock'=>__('ui.production_return.cancel_stock_shortage')]);$after=round($before-$qty,4);$stock->update(['quantity'=>$after]);StockMovement::query()->create(['product_id'=>$item->product_id,'production_return_document_id'=>$d->id,'production_return_item_id'=>$item->id,'created_by'=>$request->user()->id,'movement_type'=>'production_return_reversal','quantity'=>-$qty,'quantity_before'=>$before,'quantity_after'=>$after,'cost_amount'=>0,'notes'=>"Pembatalan penerimaan retur produksi {$d->number}"]);}
            $old=['status'=>$d->status];$d->update(['status'=>'cancelled','cancelled_by'=>$request->user()->id,'cancelled_at'=>now()]);$audit->log('CANCEL','RECEIPT_FROM_PRODUCTION',"Membatalkan penerimaan produksi {$d->number}",$d,$old,$d->fresh()->toArray(),(int)$request->user()->id);
        });
        return response()->json(['message'=>__('ui.production_return.cancelled')]);
    }

    public function export(Request $request, CompanyContext $company)
    {
        [$from,$to]=$this->dates($request);
        return Excel::download(new ProductionReturnExport($company->id(),$from,$to),'return-to-warehouse.xlsx');
    }

    private function dates(Request $request): array
    {
        $data=$request->validate(['from_date'=>['nullable','date_format:Y-m-d'],'to_date'=>['nullable','date_format:Y-m-d']]);
        if(!empty($data['from_date'])&&!empty($data['to_date'])&&$data['to_date']<$data['from_date'])throw ValidationException::withMessages(['to_date'=>__('ui.production_return.invalid_date_range')]);
        return [$data['from_date']??null,$data['to_date']??null];
    }
}

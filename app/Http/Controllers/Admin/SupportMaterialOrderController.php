<?php
namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Actions\SaveSupportMaterialOrderItemAction;
use App\Domain\Manufacturing\Queries\SupportMaterialOrderTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\SupportMaterialOrdersExport;
use App\Http\Controllers\Controller;
use App\Models\{Product, SupportMaterialOrderDocument, SupportMaterialOrderItem};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class SupportMaterialOrderController extends Controller
{
    public function index() { return view('super.support-material-orders.index'); }
    public function create(Request $request, CompanyContext $company, DocumentNumberService $numbers): View
    {
        $data = $request->validate(['document_id' => ['nullable', 'integer', 'min:1']]);
        $document = isset($data['document_id'])
            ? SupportMaterialOrderDocument::where('company_id', $company->id())
                ->with('items.product.unit')->findOrFail($data['document_id'])
            : null;

        return view('super.support-material-orders.form', [
            'document' => $document,
            'item' => null,
            'number' => $document?->number ?? $numbers->preview('support_material_order'),
        ]);
    }

    public function edit(SupportMaterialOrderItem $item, CompanyContext $company): View
    {
        $document = $item->document()->where('company_id', $company->id())->firstOrFail();
        $item->load('product.unit');

        return view('super.support-material-orders.form', [
            'document' => $document,
            'item' => $item,
            'number' => $document->number,
        ]);
    }

    public function numberPreview(DocumentNumberService $numbers): JsonResponse { return response()->json(['number' => $numbers->preview('support_material_order')]); }

    private function filters(Request $request): array
    {
        return $request->validate(['from_date'=>['nullable','date_format:Y-m-d'],'to_date'=>['nullable','date_format:Y-m-d',function($a,$v,$fail)use($request){if($v&&$request->from_date&&$v<$request->from_date)$fail(__('ui.support_material_order.invalid_range'));}]]);
    }

    public function export(Request $request, CompanyContext $company)
    {
        $f=$this->filters($request);
        return Excel::download(new SupportMaterialOrdersExport($company->id(),$f['from_date'] ?? now()->startOfMonth()->toDateString(),$f['to_date'] ?? now()->toDateString()),'support-material-orders.xlsx');
    }

    public function dt(Request $request, CompanyContext $company, SupportMaterialOrderTableQuery $query): JsonResponse
    {
        $f=$this->filters($request);
        return DataTables::eloquent($query->builder($company->id(),$f['from_date'] ?? now()->startOfMonth()->toDateString(),$f['to_date'] ?? now()->toDateString()))
            ->addIndexColumn()->addColumn('number',fn($r)=>$r->document?->number)
            ->addColumn('order_date',fn($r)=>$r->document?->order_date?->format('Y-m-d'))
            ->addColumn('code',fn($r)=>$r->product?->code)->addColumn('name',fn($r)=>$r->product?->name)
            ->addColumn('unit',fn($r)=>$r->product?->unit?->symbol?:$r->product?->unit?->name?:'—')
            ->addColumn('actions',function(SupportMaterialOrderItem $r){
                $a = '';
                if (auth()->user()->can('support-material-orders.update')) {
                    $url = e(route('super.support-material-orders.edit', $r));
                    $a .= '<li><a class="dropdown-item" href="'.$url.'">'.e(__('ui.common.edit')).'</a></li>';
                }
                if(auth()->user()->can('support-material-orders.delete')) $a.='<li><button type="button" class="dropdown-item text-danger btn-delete-support-order" data-id="'.$r->id.'">'.e(__('ui.common.delete')).'</button></li>';
                return $a?'<div class="dropdown"><button class="btn btn-sm border-0 bg-transparent p-0" data-bs-toggle="dropdown"><i data-feather="more-vertical" class="icon-sm"></i></button><ul class="dropdown-menu dropdown-menu-end">'.$a.'</ul></div>':'';
            })->rawColumns(['actions'])->toJson();
    }

    public function productsDt(SupportMaterialOrderTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->products()->with(['parentProduct:id,code', 'category:id,name']))
            ->addColumn('parent_code', fn ($p) => $p?->parentProduct?->code ?? $p?->code ?? '—')
            ->addColumn('sku_code', fn ($p) => $p?->parent_product_id ? $p->code : '—')
            ->addColumn('category_label', fn ($p) => $p?->category?->name ?? '—')
            ->addColumn('unit_label',fn(Product $p)=>$p->unit?->symbol?:$p->unit?->name?:'—')
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

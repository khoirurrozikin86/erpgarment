<?php
namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Actions\SaveSewingAction;
use App\Domain\Manufacturing\Queries\SewingTableQuery;
use App\Domain\Manufacturing\Queries\ProductionStageProgressQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\SewingExport;
use App\Http\Controllers\Controller;
use App\Models\{ProductionOrder,ProductionOrderStageEntry};
use Illuminate\Http\{JsonResponse,Request};
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class SewingController extends Controller
{
    public function index() { return view('super.sewing.index'); }
    public function create(DocumentNumberService $numbers): \Illuminate\View\View
    {
        return view('super.sewing.create', ['sewingNumber' => $numbers->preview('sewing')]);
    }

    public function preview(DocumentNumberService $numbers): JsonResponse { return response()->json(['number' => $numbers->preview('sewing')]); }
    private function filters(Request $r): array { return $r->validate(['from_date' => ['nullable','date_format:Y-m-d'], 'to_date' => ['nullable','date_format:Y-m-d', function ($a,$v,$fail) use ($r) { if ($v && $r->from_date && $v < $r->from_date) $fail(__('ui.sewing.invalid_range')); }]]); }

    public function dt(Request $r, CompanyContext $company, SewingTableQuery $query): JsonResponse
    {
        $f = $this->filters($r);
        return DataTables::eloquent($query->builder($company->id(), $f['from_date'] ?? now()->startOfMonth()->toDateString(), $f['to_date'] ?? now()->toDateString()))->addIndexColumn()
            ->addColumn('number', fn ($x) => $x->sewing_number)->addColumn('cutting_reception', fn ($x) => $x->productionOrder?->stageEntries->sum('good_quantity') ?? 0)->addColumn('date', fn ($x) => $x->processed_at?->format('Y-m-d'))
            ->addColumn('mo', fn ($x) => $x->productionOrder?->number)->addColumn('po_buyer', fn ($x) => $x->productionOrder?->buyer_po_number ?: '—')
            ->addColumn('style', fn ($x) => $x->productionOrder?->model_style ?: '—')->addColumn('product_code', fn ($x) => $x->productionOrder?->stageProductCode('sewing'))
            ->addColumn('cutting_product_code', fn ($x) => $x->productionOrder?->stageProductCode('cutting'))
            ->addColumn('product_name', fn ($x) => $x->productionOrder?->outputProduct?->name)->addColumn('color', fn ($x) => $x->productionOrder?->outputProduct?->color ?: '—')->addColumn('size', fn ($x) => $x->productionOrder?->outputProduct?->size ?: '—')->addColumn('order_quantity', fn ($x) => $x->productionOrder?->planned_quantity ?? 0)->addColumn('unit', fn ($x) => 'PCS')
            ->addColumn('material_code', fn ($x) => $x->materialProduct?->code)->addColumn('material_name', fn ($x) => $x->materialProduct?->name)
            ->addColumn('material_unit', fn ($x) => $x->materialProduct?->unit?->symbol ?: $x->materialProduct?->unit?->name ?: '—')
            ->addColumn('actions', function (ProductionOrderStageEntry $x) {
                $html = '<button type="button" class="btn btn-primary btn-sm py-0 px-2 btn-show-sewing-detail">'.e(__('ui.common.view_detail')).'</button>';
                if (auth()->user()->can('sewing.update')) $html .= '<button type="button" class="btn btn-outline-primary btn-sm p-1 btn-edit-sewing" title="'.e(__('ui.common.edit')).'" data-id="'.$x->id.'" data-number="'.e($x->sewing_number).'" data-mo="'.$x->production_order_id.'" data-mo-number="'.e($x->productionOrder?->number).'" data-product="'.e($x->productionOrder?->stageProductCode('sewing').' — '.$x->productionOrder?->outputProduct?->name).'" data-style="'.e($x->productionOrder?->model_style).'" data-po="'.e($x->productionOrder?->buyer_po_number).'" data-unit="PCS" data-input="'.e($x->input_quantity).'" data-good="'.e($x->good_quantity).'" data-reject="'.e($x->rejected_quantity).'" data-material="'.$x->material_product_id.'" data-material-label="'.e($x->materialProduct?->code.' — '.$x->materialProduct?->name).'" data-material-unit="'.e($x->materialProduct?->unit?->symbol ?: $x->materialProduct?->unit?->name).'" data-waste="'.e($x->material_waste_quantity).'" data-date="'.$x->processed_at?->format('Y-m-d').'" aria-label="'.e(__('ui.common.edit')).'"><i data-feather="edit-2" class="icon-sm"></i></button>';
                if (auth()->user()->can('sewing.delete')) $html .= '<button type="button" class="btn btn-outline-danger btn-sm p-1 btn-delete-sewing" title="'.e(__('ui.common.delete')).'" data-id="'.$x->id.'" aria-label="'.e(__('ui.common.delete')).'"><i data-feather="trash-2" class="icon-sm"></i></button>';
                return '<div class="d-flex align-items-center gap-1">'.$html.'</div>';
            })->rawColumns(['actions'])->toJson();
    }

    public function ordersDt(CompanyContext $company, SewingTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->productionOrders($company->id()))->addColumn('style', fn ($o) => $o->model_style ?: '—')
            ->addColumn('product_code', fn ($o) => $o->stageProductCode('finished_good'))->addColumn('product_name', fn ($o) => $o->outputProduct?->name)->addColumn('color', fn ($o) => $o->outputProduct?->color ?: '—')->addColumn('size', fn ($o) => $o->outputProduct?->size ?: '—')->addColumn('order_quantity', fn ($o) => $o->planned_quantity ?? 0)
            ->addColumn('unit', fn ($o) => 'PCS')
            ->addColumn('actions', fn ($o) => '<button type="button" class="btn btn-sm btn-primary btn-select-sewing-mo" data-id="'.$o->id.'" data-number="'.e($o->number).'" data-product="'.e($o->stageProductCode('finished_good').' — '.$o->outputProduct?->name).'" data-style="'.e($o->model_style).'" data-po="'.e($o->buyer_po_number).'" data-unit="PCS" data-color="'.e($o->outputProduct?->color).'" data-size="'.e($o->outputProduct?->size).'" data-available="'.e($o->available_cutting_quantity).'">'.e(__('ui.common.select')).'</button>')->rawColumns(['actions'])->toJson();
    }

    public function summaryDt(Request $request, CompanyContext $company, ProductionStageProgressQuery $query): JsonResponse
    {
        $filters = $this->filters($request);
        return DataTables::eloquent($query->builder(
            $company->id(), $filters['from_date'] ?? now()->startOfMonth()->toDateString(),
            $filters['to_date'] ?? now()->toDateString(), 'cutting', 'sewing',
        ))
            ->addColumn('color', fn (ProductionOrder $order) => $order->line_color ?: '—')
            ->addColumn('size', fn (ProductionOrder $order) => $order->line_size ?: '—')
            ->addColumn('cutting_quantity', fn (ProductionOrder $order) => $order->incoming_quantity)
            ->addColumn('sewing_quantity', fn (ProductionOrder $order) => $order->processed_quantity)
            ->addColumn('packing_quantity', fn (ProductionOrder $order) => $order->output_quantity)
            ->addColumn('wip_quantity', fn (ProductionOrder $order) => round(max(0, $order->incoming_quantity - $order->processed_quantity), 4))
            ->addColumn('shortage_quantity', fn (ProductionOrder $order) => round(max(0, $order->planned_quantity - $order->output_quantity), 4))
            ->toJson();
    }

    public function orderDetails(ProductionOrder $order, CompanyContext $company, SewingTableQuery $query): JsonResponse
    {
        abort_unless((int)$order->company_id === $company->id(), 404);
        $orders = $query->productionOrders($company->id())
            ->when($order->production_order_header_id,
                fn ($q) => $q->where('production_orders.production_order_header_id', $order->production_order_header_id),
                fn ($q) => $q->where('production_orders.number', $order->number))
            ->get();
        return response()->json(['lines' => $orders->map(fn ($detail) => [
            'production_order_id' => $detail->id,
            'code' => $detail->stageProductCode('sewing'),
            'cutting_code' => $detail->stageProductCode('cutting'),
            'name' => $detail->outputProduct?->name, 'unit' => 'PCS',
            'color' => $detail->outputProduct?->color ?: '—',
            'size' => $detail->outputProduct?->size ?: '—',
            'available' => (float)$detail->available_cutting_quantity,
            'materials' => $detail->materials->unique('product_id')->values()->map(fn ($material) => [
                'id' => $material->product_id, 'code' => $material->product?->code,
                'name' => $material->product?->name,
                'unit' => $material->product?->unit?->symbol ?: $material->product?->unit?->name,
            ]),
        ])]);
    }

    public function materialsDt(Request $r, CompanyContext $company): JsonResponse
    {
        $materials = \App\Models\ProductionOrderMaterial::query()->whereRaw('1 = 0');
        if ($r->filled('production_order_id')) { $data = $r->validate(['production_order_id' => ['required','integer']]); $order = ProductionOrder::query()->where('company_id',$company->id())->where('status','in_progress')->findOrFail($data['production_order_id']); $materials = \App\Models\ProductionOrderMaterial::query()->where('production_order_id',$order->id); }
        return DataTables::eloquent($materials->forSewing()->with('product.unit')->select('production_order_materials.*'))
            ->addColumn('code', fn ($x) => $x->product?->code)->addColumn('name', fn ($x) => $x->product?->name)
            ->addColumn('unit', fn ($x) => $x->product?->unit?->symbol ?: $x->product?->unit?->name ?: '—')
            ->addColumn('actions', fn ($x) => '<button type="button" class="btn btn-sm btn-primary btn-select-sewing-material" data-id="'.$x->product_id.'" data-label="'.e($x->product?->code.' — '.$x->product?->name).'" data-unit="'.e($x->product?->unit?->symbol ?: $x->product?->unit?->name).'">'.e(__('ui.common.select')).'</button>')->rawColumns(['actions'])->toJson();
    }

    public function export(Request $r, CompanyContext $company) { $f = $this->filters($r); return Excel::download(new SewingExport($company->id(), $f['from_date'] ?? now()->startOfMonth()->toDateString(), $f['to_date'] ?? now()->toDateString()), 'sewing.xlsx'); }
    public function store(Request $r, SaveSewingAction $action): JsonResponse
    {
        if ($r->has('lines')) {
            $data=$r->validate([
                'production_order_id'=>['required','integer'], 'sewing_date'=>['required','date_format:Y-m-d'],
                'lines'=>['required','array','min:1','max:500'],
                'lines.*.production_order_id'=>['required','integer','distinct'],
                'lines.*.material_product_id'=>['nullable','integer','exists:products,id'],
                'lines.*.input_quantity'=>['required','numeric','min:0','max:999999999999.9999'],
                'lines.*.send_to_packing'=>['required','numeric','min:0','max:999999999999.9999'],
                'lines.*.rejected_quantity'=>['required','numeric','min:0','max:999999999999.9999'],
                'lines.*.material_waste_quantity'=>['nullable','numeric','min:0','max:999999999999.9999'],
            ]);
            $action->createBatch($data,(int)$r->user()->id);
        } else {
            $action->create($this->validated($r),(int)$r->user()->id);
        }
        return response()->json(['message'=>__('ui.sewing.saved')],201);
    }
    public function update(Request $r, ProductionOrderStageEntry $entry, CompanyContext $company, SaveSewingAction $action): JsonResponse { abort_unless($entry->stage === 'sewing' && $entry->productionOrder()->where('company_id',$company->id())->exists(),404); $action->update($entry,$this->validated($r),(int)$r->user()->id); return response()->json(['message'=>__('ui.sewing.updated')]); }
    public function destroy(Request $r, ProductionOrderStageEntry $entry, CompanyContext $company, SaveSewingAction $action): JsonResponse { abort_unless($entry->stage === 'sewing' && $entry->productionOrder()->where('company_id',$company->id())->exists(),404); $action->delete($entry,(int)$r->user()->id); return response()->json(['message'=>__('ui.sewing.deleted')]); }
    private function validated(Request $r): array { return $r->validate(['production_order_id'=>['required','integer','exists:production_orders,id'],'sewing_date'=>['required','date_format:Y-m-d'],'material_product_id'=>['nullable','integer','exists:products,id'],'input_quantity'=>['required','numeric','gt:0','max:999999999999.9999'],'send_to_packing'=>['required','numeric','min:0','max:999999999999.9999'],'rejected_quantity'=>['required','numeric','min:0','max:999999999999.9999'],'material_waste_quantity'=>['nullable','numeric','min:0','max:999999999999.9999']]); }
}

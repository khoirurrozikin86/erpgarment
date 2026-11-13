<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Queries\RawMaterialOrderTableQuery;
use App\Domain\Manufacturing\Services\RawMaterialOrderService;
use App\Exports\RawMaterialOrdersExport;
use App\Http\Controllers\Controller;
use App\Models\{ProductionOrder, RawMaterialOrderDocument, RawMaterialOrderItem};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class RawMaterialOrderController extends Controller
{
    public function index(): View { return view('super.raw-material-orders.index'); }

    public function create(): View
    {
        return view('super.raw-material-orders.form', ['document' => null, 'items' => []]);
    }

    public function edit(RawMaterialOrderDocument $document, CompanyContext $company): View
    {
        $document = RawMaterialOrderDocument::query()->where('company_id', $company->id())
            ->with(['items.product.unit', 'items.sourceMaterial.productionOrder.outputProduct'])->findOrFail($document->id);
        $items = $document->items->map(fn (RawMaterialOrderItem $item) => [
            'number' => $item->number,
            'source_id' => $item->production_order_material_id, 'product_id' => $item->product_id,
            'code' => $item->product?->code, 'name' => $item->product?->name,
            'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name,
            'style_code' => $item->style_code, 'quantity' => (float) $item->quantity,
        ])->all();
        return view('super.raw-material-orders.form', ['document' => $document, 'items' => $items]);
    }

    public function dt(Request $request, CompanyContext $company, RawMaterialOrderTableQuery $query): JsonResponse
    {
        $filters = $this->dateFilters($request);
        return DataTables::eloquent($query->rows($company->id(), $filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('number', fn (RawMaterialOrderItem $item) => $item->number)
            ->addColumn('order_date', fn (RawMaterialOrderItem $item) => $item->document->order_date?->format('Y-m-d'))
            ->addColumn('mo_number', fn (RawMaterialOrderItem $item) => $item->document->production_order_number)
            ->addColumn('code', fn (RawMaterialOrderItem $item) => $item->product->code)
            ->addColumn('name', fn (RawMaterialOrderItem $item) => $item->product->name)
            ->addColumn('unit', fn (RawMaterialOrderItem $item) => $item->product->unit?->symbol ?: $item->product->unit?->name ?: '—')
            ->addColumn('actions', function (RawMaterialOrderItem $item) {
                $edit = e(route('super.raw-material-orders.edit', $item->raw_material_order_document_id));
                $delete = e(route('super.raw-material-orders.destroy', $item->raw_material_order_document_id));
                return '<div class="dropdown d-inline-block"><button class="btn btn-sm bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="'.e(__('ui.common.actions')).'"><i data-feather="more-vertical" class="icon-sm"></i></button><ul class="dropdown-menu dropdown-menu-end shadow-sm"><li><a class="dropdown-item d-flex align-items-center gap-2" href="'.$edit.'"><i data-feather="edit-2" class="icon-sm"></i>'.e(__('ui.common.edit')).'</a></li><li><button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger btn-delete-raw-material-order" data-url="'.$delete.'" data-number="'.e($item->document->number).'"><i data-feather="trash-2" class="icon-sm"></i>'.e(__('ui.common.delete')).'</button></li></ul></div>';
            })
            ->rawColumns(['actions'])->toJson();
    }

    public function mosDt(CompanyContext $company, RawMaterialOrderTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->moGroups($company->id()))
            ->addColumn('mo_date', fn (ProductionOrder $order) => $order->po_date?->format('Y-m-d'))
            ->addColumn('style_code', fn (ProductionOrder $order) => $order->outputProduct?->style_code ?: '—')
            ->addColumn('actions', fn (ProductionOrder $order) => '<button type="button" class="btn btn-sm btn-primary btn-select-raw-mo" data-number="'.e($order->number).'" data-date="'.$order->po_date?->format('Y-m-d').'" data-style="'.e($order->outputProduct?->style_code ?: '—').'">'.e(__('ui.common.select')).'</button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function moMaterials(Request $request, CompanyContext $company, RawMaterialOrderTableQuery $query): JsonResponse
    {
        $data = $request->validate(['production_order_number' => ['required', 'string', 'max:100']]);
        abort_unless(ProductionOrder::query()->where('company_id', $company->id())->where('number', $data['production_order_number'])->exists(), 404);
        $items = $query->materialsForMo($company->id(), $data['production_order_number'])->map(fn ($source) => [
            'source_id' => $source->id, 'product_id' => $source->product_id,
            'code' => $source->product?->code, 'name' => $source->product?->name,
            'unit' => $source->product?->unit?->symbol ?: $source->product?->unit?->name,
            'style_code' => $source->productionOrder?->outputProduct?->style_code,
            'quantity' => (float) $source->required_quantity,
        ])->values();
        return response()->json(['items' => $items]);
    }

    public function store(Request $request, RawMaterialOrderService $service): JsonResponse
    {
        $document = $service->create($this->validated($request), (int) $request->user()->id);
        return response()->json(['message' => __('ui.raw_material_order.saved'), 'url' => route('super.raw-material-orders.index'), 'number' => $document->number], 201);
    }

    public function update(Request $request, RawMaterialOrderDocument $document, RawMaterialOrderService $service): JsonResponse
    {
        $document = $service->update($document, $this->validated($request), (int) $request->user()->id);
        return response()->json(['message' => __('ui.raw_material_order.updated'), 'url' => route('super.raw-material-orders.index'), 'number' => $document->number]);
    }

    public function destroy(Request $request, RawMaterialOrderDocument $document, RawMaterialOrderService $service): JsonResponse
    {
        $service->delete($document, (int) $request->user()->id);
        return response()->json(['message' => __('ui.raw_material_order.deleted')]);
    }

    public function export(Request $request, CompanyContext $company)
    {
        $filters = $this->dateFilters($request);
        return Excel::download(new RawMaterialOrdersExport($company->id(), $filters['from_date'] ?? null, $filters['to_date'] ?? null), 'raw-material-orders.xlsx');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'order_date' => ['required', 'date_format:Y-m-d'],
            'production_order_number' => ['required', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.production_order_material_id' => ['required', 'integer', 'distinct', 'exists:production_order_materials,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
        ]);
    }

    private function dateFilters(Request $request): array
    {
        return $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) $fail(__('ui.raw_material_order.invalid_date_range'));
            }],
        ]);
    }
}

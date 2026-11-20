<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Manufacturing\Queries\RawMaterialOrderTableQuery;
use App\Domain\Manufacturing\Services\RawMaterialOrderGroups;
use App\Domain\Manufacturing\Services\RawMaterialOrderService;
use App\Exports\RawMaterialOrdersExport;
use App\Http\Controllers\Controller;
use App\Models\{Product, ProductionOrder, ProductionOrderMaterial, RawMaterialOrderDocument, RawMaterialOrderItem};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class RawMaterialOrderController extends Controller
{
    public function index(): View { return view('super.raw-material-orders.index'); }

    public function create(): View
    {
        return view('super.raw-material-orders.form', ['document' => null, 'items' => [], 'productionDetails' => []]);
    }

    public function edit(RawMaterialOrderDocument $document, CompanyContext $company, RawMaterialOrderGroups $groups): View
    {
        $document = RawMaterialOrderDocument::query()->where('company_id', $company->id())
            ->with(['items.product.unit', 'items.sourceMaterial.billOfMaterial:id,bom_type', 'items.product.parentProduct:id,code', 'items.product.variants:id,parent_product_id,code,variant_key'])->findOrFail($document->id);
        $items = $this->documentItems($document, $groups);
        if ($document->production_order_number) {
            $items = $groups->group($items);
        }

        return view('super.raw-material-orders.form', [
            'document' => $document,
            'items' => $items->all(),
            'productionDetails' => $document->production_order_number
                ? $this->productionDetails($company->id(), $document->production_order_number) : [],
        ]);
    }

    private function documentItems(RawMaterialOrderDocument $document, RawMaterialOrderGroups $groups): \Illuminate\Support\Collection
    {
        return $document->items->map(function (RawMaterialOrderItem $item) use ($groups) {
            $color = $item->color ?: $item->sourceMaterial?->color ?: $item->product?->color;
            $size = $item->size ?: $item->sourceMaterial?->size ?: $item->product?->size;

            return [
                ...$groups->itemCodes($item->product, $color, $size),
                'number' => $item->number,
                'source_id' => $item->production_order_material_id, 'product_id' => $item->product_id,
                'code' => $item->product?->code, 'name' => $item->product?->name,
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name,
                'style_code' => $item->style_code, 'quantity' => (float) $item->quantity,
                'issued_quantity' => (float) $item->issued_quantity,
                'category' => $item->category ?: $this->materialCategory($item->sourceMaterial, $item->product),
                'color' => $color, 'size' => $size,
            ];
        });
    }

    public function pdf(RawMaterialOrderDocument $document, CompanyContext $company, CompanyPdfBrandingService $branding, RawMaterialOrderGroups $groups)
    {
        abort_unless((int) $document->company_id === $company->id(), 404);
        $document->load(['creator', 'items.product.unit', 'items.sourceMaterial.billOfMaterial:id,bom_type', 'items.product.parentProduct:id,code', 'items.product.variants:id,parent_product_id,code,variant_key']);

        $items = $groups->group($this->documentItems($document, $groups));
        $productionDetails = filled($document->production_order_number)
            ? $this->productionDetails($company->id(), $document->production_order_number) : [];

        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $document->number).'.pdf';

        return Pdf::loadView('super.raw-material-orders.pdf', [...$branding->data(), ...compact('document', 'items', 'productionDetails')])
            ->setPaper('a4')->stream($filename);
    }

    public function dt(Request $request, CompanyContext $company, RawMaterialOrderTableQuery $query, RawMaterialOrderGroups $groups): JsonResponse
    {
        $filters = $this->dateFilters($request);
        return DataTables::eloquent($query->documents($company->id(), $filters['from_date'] ?? now()->startOfMonth()->toDateString(), $filters['to_date'] ?? now()->toDateString()))
            ->addColumn('order_date', fn (RawMaterialOrderDocument $document) => $document->order_date?->format('Y-m-d'))
            ->addColumn('mo_number', fn (RawMaterialOrderDocument $document) => $document->production_order_number ?: 'Manual')
            ->addColumn('item_details', fn (RawMaterialOrderDocument $document) => view(
                'super.raw-material-orders.item-details',
                ['items' => $groups->group($this->documentItems($document, $groups))]
            )->render())
            ->addColumn('actions', function (RawMaterialOrderDocument $document) {
                $print = e(route('super.raw-material-orders.pdf', $document));
                $edit = e(route('super.raw-material-orders.edit', $document));
                $delete = e(route('super.raw-material-orders.destroy', $document));
                $printButton = '<a class="btn btn-outline-primary btn-sm text-nowrap" href="'.$print.'" target="_blank" rel="noopener">'
                    .'<i data-feather="printer" class="icon-sm me-1"></i>'.e(__('variants.print_pdf')).'</a>';

                return '<div class="d-flex align-items-center justify-content-center gap-2">'.$printButton.'<div class="dropdown d-inline-block"><button class="btn btn-sm bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="'.e(__('ui.common.actions')).'"><i data-feather="more-vertical" class="icon-sm"></i></button><ul class="dropdown-menu dropdown-menu-end shadow-sm"><li><a class="dropdown-item d-flex align-items-center gap-2" href="'.$edit.'"><i data-feather="edit-2" class="icon-sm"></i>'.e(__('ui.common.edit')).'</a></li><li><button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger btn-delete-raw-material-order" data-url="'.$delete.'" data-number="'.e($document->number).'"><i data-feather="trash-2" class="icon-sm"></i>'.e(__('ui.common.delete')).'</button></li></ul></div></div>';
            })
            ->rawColumns(['item_details', 'actions'])->toJson();
    }

    public function mosDt(CompanyContext $company, RawMaterialOrderTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->moGroups($company->id()))
            ->addColumn('mo_date', fn (ProductionOrder $order) => $order->po_date?->format('Y-m-d'))
            ->addColumn('style_code', fn (ProductionOrder $order) => $order->model_style ?: '—')
            ->addColumn('variant_details', function (ProductionOrder $order) use ($company) {
                $details = ProductionOrder::query()
                    ->where('company_id', $company->id())
                    ->where('number', $order->number)
                    ->with('outputProduct:id,code,name,style_code,color,size')
                    ->get();

                return $details->map(function (ProductionOrder $detail) {
                    $style = e($detail->model_style ?: '—');
                    $color = e($detail->outputProduct?->color ?: '—');
                    $size = e($detail->outputProduct?->size ?: '—');
                    $quantity = rtrim(rtrim(number_format((float) $detail->planned_quantity, 4, ',', '.'), '0'), ',');
                    return '<span class="d-block text-nowrap">' . $style . ' / ' . $color . ' / ' . $size . ' / ' . $quantity . '</span>';
                })->implode('');
            })
            ->addColumn('actions', fn (ProductionOrder $order) => '<button type="button" class="btn btn-sm btn-primary btn-select-raw-mo" data-number="'.e($order->number).'" data-date="'.$order->po_date?->format('Y-m-d').'" data-style="'.e($order->model_style ?: '—').'">'.e(__('ui.common.select')).'</button>')
            ->rawColumns(['variant_details', 'actions'])->toJson();
    }

    public function moMaterials(Request $request, CompanyContext $company, RawMaterialOrderTableQuery $query, RawMaterialOrderGroups $groups): JsonResponse
    {
        $data = $request->validate([
            'production_order_number' => ['required', 'string', 'max:100'],
            'category' => ['nullable', 'in:all,fabric,accessory,general'],
            'grouped' => ['sometimes', 'boolean'],
        ]);
        $productionDetails = $this->productionDetails($company->id(), $data['production_order_number']);
        abort_if($productionDetails === [], 404);
        $items = $query->materialsForMo($company->id(), $data['production_order_number'], $data['category'] ?? 'all')
            ->map(function (ProductionOrderMaterial $source) use ($groups) {
                $color = $source->color ?: $source->product?->color;
                $size = $source->size ?: $source->product?->size;

                return [
                    ...$groups->itemCodes($source->product, $color, $size),
                    'source_id' => $source->id, 'product_id' => $source->product_id,
                    'code' => $source->product?->code, 'name' => $source->product?->name,
                    'unit' => $source->product?->unit?->symbol ?: $source->product?->unit?->name,
                    'style_code' => $source->productionOrder?->model_style,
                    'color' => $color, 'size' => $size,
                    'category' => $source->billOfMaterial?->bom_type ?? $source->productionOrder?->billOfMaterial?->bom_type,
                    'quantity' => (float) $source->required_quantity,
                ];
            })->values();

        return response()->json([
            'items' => $request->boolean('grouped') ? $groups->group($items) : $items,
            'production_details' => $productionDetails,
        ]);
    }

    private function productionDetails(int $companyId, string $number): array
    {
        return ProductionOrder::query()->where('company_id', $companyId)->where('number', $number)
            ->with('outputProduct:id,style_code,color,size')
            ->orderBy('id')->get()
            ->map(fn (ProductionOrder $line) => [
                'style' => $line->model_style,
                'color' => $line->line_color,
                'size' => $line->line_size,
                'quantity' => (float) $line->planned_quantity,
            ])->all();
    }

    private function materialCategory(?ProductionOrderMaterial $source, ?Product $product): string
    {
        return $source?->billOfMaterial?->bom_type ?: match ($product?->garment_item_type) {
            'fabric' => 'fabric',
            'accessory' => 'accessory',
            default => 'general',
        };
    }

    public function manualProducts(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Product::query()
            ->where('is_active', true)
            ->where('track_stock', true)
            ->with('unit:id,name,symbol')
            ->select(['id', 'code', 'name', 'unit_id', 'style_code', 'color', 'size'])
            ->when($filters['q'] ?? null, function ($builder, $search) {
                $builder->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('style_code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');

        return DataTables::eloquent($query)
            ->addColumn('unit', fn (Product $product) => $product->unit?->symbol ?: $product->unit?->name ?: '—')
            ->addColumn('actions', fn (Product $product) => '<button type="button" class="btn btn-sm btn-primary btn-select-manual-material" data-id="'.(int) $product->id.'" data-code="'.e($product->code).'" data-name="'.e($product->name).'" data-unit="'.e($product->unit?->symbol ?: $product->unit?->name ?: '—').'" data-style="'.e($product->style_code ?: '').'" data-color="'.e($product->color ?: '').'" data-size="'.e($product->size ?: '').'">'.e(__('ui.common.select')).'</button>')
            ->rawColumns(['actions'])
            ->toJson();
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
        return Excel::download(new RawMaterialOrdersExport($company->id(), $filters['from_date'] ?? now()->startOfMonth()->toDateString(), $filters['to_date'] ?? now()->toDateString()), 'raw-material-orders.xlsx');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'order_date' => ['required', 'date_format:Y-m-d'],
            'mode' => ['required', 'in:mo,manual'],
            'production_order_number' => ['required_if:mode,mo', 'nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.production_order_material_id' => ['required_if:mode,mo', 'nullable', 'integer', 'exists:production_order_materials,id'],
            'items.*.product_id' => ['required_if:mode,manual', 'nullable', 'integer', 'exists:products,id'],
            'items.*.style_code' => ['nullable', 'string', 'max:100'],
            'items.*.color' => ['nullable', 'string', 'max:80'],
            'items.*.size' => ['nullable', 'string', 'max:40'],
            'items.*.category' => ['nullable', 'in:fabric,accessory,general'],
            'category' => ['nullable', 'in:all,fabric,accessory,general'],
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

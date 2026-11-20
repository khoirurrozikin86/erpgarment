<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Actions\SaveFinishedGoodsAction;
use App\Domain\Manufacturing\Queries\FinishedGoodsTableQuery;
use App\Domain\Manufacturing\Services\ProductionStageBalance;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\FinishedGoodsExport;
use App\Http\Controllers\Controller;
use App\Models\ProductionOrderStageEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class FinishedGoodsController extends Controller
{
    public function index() { return view('super.finished-goods.index'); }

    public function create(DocumentNumberService $numbers): View
    {
        return view('super.finished-goods.form', [
            'entry' => null,
            'number' => $numbers->preview('finished_goods'),
            'available' => null,
        ]);
    }

    public function edit(ProductionOrderStageEntry $entry, CompanyContext $company, ProductionStageBalance $balance): View
    {
        abort_unless($entry->stage === 'finishing' && $entry->productionOrder()->where('company_id', $company->id())->exists(), 404);
        $entry->load('productionOrder.outputProduct.unit');

        return view('super.finished-goods.form', [
            'entry' => $entry,
            'number' => $entry->finished_goods_number,
            'available' => $balance->remaining($entry->productionOrder, 'finishing', $entry->id),
        ]);
    }

    public function materials(ProductionOrderStageEntry $entry, CompanyContext $company): View
    {
        abort_unless($entry->stage === 'finishing' && $entry->productionOrder()->where('company_id', $company->id())->exists(), 404);
        $entry->load([
            'productionOrder.outputProduct.unit',
            'productionOrder.billOfMaterial',
            'productionOrder.materials.billOfMaterial',
            'productionOrder.materials.product.unit',
        ]);

        $order = $entry->productionOrder;
        $materials = $order->materials->map(function ($material) use ($order) {
            $bom = $material->bill_of_material_id ? $material->billOfMaterial : $order->billOfMaterial;
            $output = (float) $bom?->output_quantity;

            return [
                'code' => $material->product?->code,
                'name' => $material->product?->variant_display_name,
                'unit' => $material->product?->unit?->symbol ?: $material->product?->unit?->name,
                'consumption_per_piece' => $output > 0 ? (float) $material->quantity_per_output / $output : null,
            ];
        });

        return view('super.finished-goods.materials', compact('entry', 'order', 'materials'));
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) $fail(__('ui.finished_goods.invalid_range'));
            }],
        ]);
    }

    public function dt(Request $request, CompanyContext $company, FinishedGoodsTableQuery $query): JsonResponse
    {
        $filters = $this->filters($request);
        return DataTables::eloquent($query->builder($company->id(), $filters['from_date'] ?? now()->startOfMonth()->toDateString(), $filters['to_date'] ?? now()->toDateString()))->addIndexColumn()
            ->addColumn('date', fn ($row) => $row->processed_at?->format('Y-m-d'))
            ->addColumn('style', fn ($row) => $row->productionOrder?->model_style ?: '—')
            ->addColumn('item_code', fn ($row) => $row->productionOrder?->outputProduct?->code)
            ->addColumn('item_name', fn ($row) => $row->productionOrder?->outputProduct?->variant_display_name)->addColumn('color', fn ($row) => $row->productionOrder?->outputProduct?->color ?: '—')->addColumn('size', fn ($row) => $row->productionOrder?->outputProduct?->size ?: '—')->addColumn('order_quantity', fn ($row) => $row->productionOrder?->planned_quantity ?? 0)
            ->addColumn('mo', fn ($row) => $row->productionOrder?->number)
            ->addColumn('po_buyer', fn ($row) => $row->productionOrder?->buyer_po_number ?: '—')
            ->addColumn('actions', function (ProductionOrderStageEntry $row) {
                $materialsUrl = e(route('super.finished-goods.materials', $row));
                $buttons = '<a href="'.$materialsUrl.'" class="btn btn-primary btn-sm text-nowrap">'.e(__('ui.common.view_detail')).'</a>';
                if (auth()->user()->can('finished-goods.update')) {
                    $edit = e(route('super.finished-goods.edit', $row));
                    $label = e(__('ui.common.edit'));
                    $buttons .= '<a href="'.$edit.'" class="btn btn-outline-primary btn-sm p-1" title="'.$label.'" aria-label="'.$label.'"><i data-feather="edit-2" class="icon-sm"></i></a>';
                }
                if (auth()->user()->can('finished-goods.delete')) $buttons .= '<button class="btn btn-outline-danger btn-sm p-1 js-delete-finished" title="'.e(__('ui.common.delete')).'" data-id="'.$row->id.'"><i data-feather="trash-2" class="icon-sm"></i></button>';
                return '<div class="d-flex gap-1">'.$buttons.'</div>';
            })->rawColumns(['actions'])->toJson();
    }

    public function ordersDt(CompanyContext $company, FinishedGoodsTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->eligibleOrders($company->id()))
            ->addColumn('parent_code', fn ($order) => $order->outputProduct?->parentProduct?->code ?? $order->outputProduct?->code ?? '—')
            ->addColumn('sku_code', fn ($order) => $order->outputProduct?->parent_product_id ? $order->outputProduct->code : '—')
            ->addColumn('category_label', fn ($order) => $order->outputProduct?->category?->name ?? '—')
            ->addColumn('style', fn ($order) => $order->model_style ?: '—')
            ->addColumn('item_code', fn ($order) => $order->outputProduct?->code)
            ->addColumn('item_name', fn ($order) => $order->outputProduct?->variant_display_name)->addColumn('color', fn ($order) => $order->outputProduct?->color ?: '—')->addColumn('size', fn ($order) => $order->outputProduct?->size ?: '—')->addColumn('order_quantity', fn ($order) => $order->planned_quantity ?? 0)
            ->addColumn('unit', fn ($order) => $order->outputProduct?->unit?->symbol ?: $order->outputProduct?->unit?->name ?: '—')
            ->addColumn('actions', fn ($order) => '<button type="button" class="btn btn-primary btn-sm js-select-finished-mo" data-id="'.$order->id.'" data-no="'.e($order->number).'" data-style="'.e($order->model_style).'" data-code="'.e($order->outputProduct?->code).'" data-name="'.e($order->outputProduct?->variant_display_name).'" data-unit="'.e($order->outputProduct?->unit?->symbol ?: $order->outputProduct?->unit?->name).'" data-po="'.e($order->buyer_po_number).'" data-available="'.e($order->available_packing_quantity).'">'.e(__('ui.common.select')).'</button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function preview(DocumentNumberService $numbers): JsonResponse { return response()->json(['number' => $numbers->preview('finished_goods')]); }
    public function export(Request $request, CompanyContext $company) { $filters = $this->filters($request); return Excel::download(new FinishedGoodsExport($company->id(), $filters['from_date'] ?? now()->startOfMonth()->toDateString(), $filters['to_date'] ?? now()->toDateString()), 'finished-goods.xlsx'); }
    public function store(Request $request, SaveFinishedGoodsAction $action): JsonResponse { $action->create($this->validated($request), (int) $request->user()->id); return response()->json(['message' => __('ui.finished_goods.saved')], 201); }

    public function update(Request $request, ProductionOrderStageEntry $entry, CompanyContext $company, SaveFinishedGoodsAction $action): JsonResponse
    {
        abort_unless($entry->stage === 'finishing' && $entry->productionOrder()->where('company_id', $company->id())->exists(), 404);
        $action->update($entry, $this->validated($request), (int) $request->user()->id);
        return response()->json(['message' => __('ui.finished_goods.updated')]);
    }

    public function destroy(Request $request, ProductionOrderStageEntry $entry, CompanyContext $company, SaveFinishedGoodsAction $action): JsonResponse
    {
        abort_unless($entry->stage === 'finishing' && $entry->productionOrder()->where('company_id', $company->id())->exists(), 404);
        $action->delete($entry, (int) $request->user()->id);
        return response()->json(['message' => __('ui.finished_goods.deleted')]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'production_order_id' => ['required', 'integer', 'exists:production_orders,id'],
            'finished_goods_date' => ['required', 'date_format:Y-m-d'],
            'receive_from_packing' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'finished_good_quantity' => ['required', 'numeric', 'min:0', 'max:999999999999.9999'],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Subcontract\Actions\SaveSubcontractSemiFinishedAction;
use App\Domain\Subcontract\Queries\SubcontractSemiFinishedTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Http\Controllers\Controller;
use App\Models\{Product, ProductionOrder, SubcontractSemiFinishedDocument, SubcontractSemiFinishedItem};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SubcontractSemiFinishedController extends Controller
{
    public function index(): View { return view('super.subcontract-semi-finished.index'); }

    public function dt(Request $request, CompanyContext $company, SubcontractSemiFinishedTableQuery $query): JsonResponse
    {
        $filters = $request->validate(['from_date' => ['nullable', 'date'], 'to_date' => ['nullable', 'date', 'after_or_equal:from_date']]);
        return DataTables::eloquent($query->builder($company->id(), $filters['from_date'] ?? now()->startOfMonth()->toDateString(), $filters['to_date'] ?? now()->toDateString()))
            ->addColumn('number_label', fn (SubcontractSemiFinishedItem $row) => $row->document?->number ?? '—')
            ->addColumn('date_label', fn (SubcontractSemiFinishedItem $row) => $row->document?->production_date?->format('d-m-Y') ?? '—')
            ->addColumn('description_label', fn (SubcontractSemiFinishedItem $row) => $row->document?->description ?: '—')
            ->addColumn('buyer_po_label', fn (SubcontractSemiFinishedItem $row) => $row->document?->buyer_po_number ?: '—')
            ->addColumn('style_label', fn (SubcontractSemiFinishedItem $row) => $row->product?->style_code ?: '—')
            ->addColumn('product_code', fn (SubcontractSemiFinishedItem $row) => $row->product?->code ?? '—')
            ->addColumn('product_name', fn (SubcontractSemiFinishedItem $row) => $row->product?->name ?? '—')
            ->addColumn('unit_label', fn (SubcontractSemiFinishedItem $row) => $row->product?->unit?->symbol ?? $row->product?->unit?->name ?? '—')
            ->addColumn('quantity_label', fn (SubcontractSemiFinishedItem $row) => number_format((float) $row->quantity, 4, ',', '.'))
            ->addColumn('mo_label', fn (SubcontractSemiFinishedItem $row) => $row->document?->productionOrder?->number ?? '—')
            ->addColumn('actions', function (SubcontractSemiFinishedItem $row) {
                $id = (int) $row->document_id;
                $actions = '<div class="d-flex justify-content-end gap-1">';
                if (auth()->user()->can('subcontract-semi-finished.update')) $actions .= '<button type="button" class="btn btn-sm btn-outline-primary js-edit-semi-finished" data-id="'.$id.'" title="'.e(__('ui.common.edit')).'"><i data-feather="edit-2" class="icon-sm"></i></button>';
                if (auth()->user()->can('subcontract-semi-finished.delete')) $actions .= '<button type="button" class="btn btn-sm btn-outline-danger js-delete-semi-finished" data-id="'.$id.'" title="'.e(__('ui.common.delete')).'"><i data-feather="trash-2" class="icon-sm"></i></button>';
                return $actions.'</div>';
            })->rawColumns(['actions'])->toJson();
    }

    public function productsDt(Request $request, SubcontractSemiFinishedTableQuery $query): JsonResponse
    {
        abort_unless(auth()->user()->can('subcontract-semi-finished.create') || auth()->user()->can('subcontract-semi-finished.update'), 403);
        return DataTables::eloquent($query->products())
            ->addColumn('unit_label', fn (Product $product) => $product->unit?->symbol ?? $product->unit?->name ?? '—')
            ->addColumn('style_label', fn (Product $product) => $product->style_code ?: '—')
            ->addColumn('actions', fn (Product $product) => '<button type="button" class="btn btn-sm btn-primary js-select-semi-finished-product" data-id="'.$product->id.'" data-code="'.e($product->code).'" data-name="'.e($product->name).'" data-unit="'.e($product->unit?->symbol ?? $product->unit?->name ?? '').'">'.e(__('ui.common.select')).'</button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function productionOrdersDt(CompanyContext $company, SubcontractSemiFinishedTableQuery $query): JsonResponse
    {
        abort_unless(auth()->user()->can('subcontract-semi-finished.create') || auth()->user()->can('subcontract-semi-finished.update'), 403);
        return DataTables::eloquent($query->productionOrders($company->id()))
            ->addColumn('product_label', fn (ProductionOrder $order) => $order->outputProduct ? $order->outputProduct->code.' — '.$order->outputProduct->name : '—')
            ->addColumn('actions', fn (ProductionOrder $order) => '<button type="button" class="btn btn-sm btn-primary js-select-semi-finished-mo" data-id="'.$order->id.'" data-number="'.e($order->number).'" data-buyer-po="'.e($order->buyer_po_number ?? '').'">'.e(__('ui.common.select')).'</button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function numberPreview(DocumentNumberService $numbers): JsonResponse { return response()->json(['number' => $numbers->preview('subcontract_semi_finished')]); }

    public function show(SubcontractSemiFinishedDocument $document, CompanyContext $company): JsonResponse
    {
        abort_unless((int) $document->company_id === $company->id(), 404);
        $document->load(['items.product:id,code,name,unit_id,style_code', 'items.product.unit:id,name,symbol', 'productionOrder:id,number,buyer_po_number']);
        $item = $document->items->first();
        return response()->json(['id' => $document->id, 'number' => $document->number, 'production_date' => $document->production_date?->toDateString(), 'production_order_id' => $document->production_order_id, 'mo_number' => $document->productionOrder?->number, 'buyer_po_number' => $document->buyer_po_number, 'description' => $document->description, 'product' => $item ? ['id' => $item->product_id, 'code' => $item->product?->code, 'name' => $item->product?->name, 'style' => $item->product?->style_code, 'unit' => $item->product?->unit?->symbol ?? $item->product?->unit?->name] : null, 'quantity' => $item?->quantity]);
    }

    public function store(Request $request, CompanyContext $company, SaveSubcontractSemiFinishedAction $action): JsonResponse
    {
        $data = $this->validated($request, $company);
        $document = $action->create($data, $company->id(), (int) $request->user()->id);
        return response()->json(['message' => __('ui.subcontract_semi_finished.saved'), 'id' => $document->id, 'number' => $document->number]);
    }

    public function update(Request $request, SubcontractSemiFinishedDocument $document, CompanyContext $company, SaveSubcontractSemiFinishedAction $action): JsonResponse
    {
        abort_unless((int) $document->company_id === $company->id(), 404);
        $action->update($document, $this->validated($request, $company), (int) $request->user()->id);
        return response()->json(['message' => __('ui.subcontract_semi_finished.saved')]);
    }

    public function destroy(Request $request, SubcontractSemiFinishedDocument $document, CompanyContext $company, SaveSubcontractSemiFinishedAction $action): JsonResponse
    {
        abort_unless((int) $document->company_id === $company->id(), 404);
        $action->delete($document, (int) $request->user()->id);
        return response()->json(['message' => __('ui.subcontract_semi_finished.deleted')]);
    }

    private function validated(Request $request, CompanyContext $company): array
    {
        return Validator::make($request->all(), [
            'production_date' => ['required', 'date'],
            'production_order_id' => ['nullable', 'integer', function ($attribute, $value, $fail) use ($company) { if (! ProductionOrder::query()->whereKey($value)->where('company_id', $company->id())->exists()) $fail(__('ui.subcontract_semi_finished.invalid_mo')); }],
            'buyer_po_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'product_id' => ['required', 'integer', function ($attribute, $value, $fail) { if (! app(SubcontractSemiFinishedTableQuery::class)->products()->whereKey($value)->exists()) $fail(__('ui.subcontract_semi_finished.invalid_product')); }],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:9999999999999999.9999'],
        ])->validate();
    }
}

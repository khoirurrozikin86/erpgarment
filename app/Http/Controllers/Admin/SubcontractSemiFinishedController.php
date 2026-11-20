<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Subcontract\Actions\SaveSubcontractSemiFinishedAction;
use App\Domain\Subcontract\Queries\{SubcontractProductColumns, SubcontractSemiFinishedTableQuery};
use App\Domain\Settings\Services\DocumentNumberService;
use App\Http\Controllers\Controller;
use App\Models\{Product, ProductionOrder, SubcontractSemiFinishedDocument, SubcontractSemiFinishedItem};
use Illuminate\Http\{JsonResponse, RedirectResponse, Request};
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SubcontractSemiFinishedController extends Controller
{
    public function index(): View { return view('super.subcontract-semi-finished.index'); }

    public function dt(Request $request, CompanyContext $company, SubcontractSemiFinishedTableQuery $query): JsonResponse
    {
        $filters = $request->validate(['from_date' => ['nullable', 'date'], 'to_date' => ['nullable', 'date', 'after_or_equal:from_date']]);
        $table = DataTables::eloquent($query->builder($company->id(), $filters['from_date'] ?? now()->startOfMonth()->toDateString(), $filters['to_date'] ?? now()->toDateString()))
            ->addColumn('number_label', fn (SubcontractSemiFinishedItem $row) => $row->document?->number ?? '—')
            ->addColumn('date_label', fn (SubcontractSemiFinishedItem $row) => $row->document?->production_date?->format('d-m-Y') ?? '—')
            ->addColumn('description_label', fn (SubcontractSemiFinishedItem $row) => $row->document?->description ?: '—')
            ->addColumn('buyer_po_label', fn (SubcontractSemiFinishedItem $row) => $row->document?->buyer_po_number ?: '—')
            ->addColumn('style_label', fn (SubcontractSemiFinishedItem $row) => $row->product?->style_code ?: '—')
            ->addColumn('unit_label', fn (SubcontractSemiFinishedItem $row) => $row->product?->unit?->symbol ?? $row->product?->unit?->name ?? '—')
            ->addColumn('quantity_label', fn (SubcontractSemiFinishedItem $row) => \App\Support\DisplayNumber::format((float) $row->quantity, 4))
            ->addColumn('mo_label', fn (SubcontractSemiFinishedItem $row) => $row->document?->productionOrder?->number ?? '—')
            ->addColumn('actions', function (SubcontractSemiFinishedItem $row) {
                $id = (int) $row->document_id;
                $actions = '<div class="d-flex justify-content-end gap-1">';
                if (auth()->user()->can('subcontract-semi-finished.update')) $actions .= '<a class="btn btn-sm btn-outline-primary" href="'.e(route('super.subcontract.semi-finished.edit', $id)).'" title="'.e(__('ui.common.edit')).'"><i data-feather="edit-2" class="icon-sm"></i></a>';
                if (auth()->user()->can('subcontract-semi-finished.delete')) $actions .= '<button type="button" class="btn btn-sm btn-outline-danger js-delete-semi-finished" data-id="'.$id.'" title="'.e(__('ui.common.delete')).'"><i data-feather="trash-2" class="icon-sm"></i></button>';
                return $actions.'</div>';
            })->rawColumns(['actions']);
        foreach (SubcontractProductColumns::HEADINGS as $key => $label) {
            $table->addColumn('item_'.$key, fn (SubcontractSemiFinishedItem $row) =>
                SubcontractProductColumns::values($row->product)[$key]);
        }

        return $table->toJson();
    }

    public function productsDt(Request $request, SubcontractSemiFinishedTableQuery $query): JsonResponse
    {
        abort_unless(auth()->user()->can('subcontract-semi-finished.create') || auth()->user()->can('subcontract-semi-finished.update'), 403);
        $table = DataTables::eloquent($query->products())
            ->addColumn('unit_label', fn (Product $product) => $product->unit?->symbol ?? $product->unit?->name ?? '—')
            ->addColumn('style_label', fn (Product $product) => $product->style_code ?: '—')
            ->addColumn('actions', fn () => '<button type="button" class="btn btn-sm btn-primary js-select-semi-finished-product">'.e(__('ui.common.select')).'</button>');
        foreach (SubcontractProductColumns::HEADINGS as $key => $label) {
            $table->addColumn('item_'.$key, fn (Product $product) => SubcontractProductColumns::values($product)[$key]);
        }

        $table->filterColumn('parent_code', fn ($query, $keyword) => $query->where(function ($query) use ($keyword) {
                $query->whereHas('parentProduct', fn ($parent) => $parent->where('code', 'like', "%{$keyword}%"))
                    ->orWhere(fn ($main) => $main->whereNull('parent_product_id')->where('code', 'like', "%{$keyword}%"));
            }))
            ->filterColumn('category_name', fn ($query, $keyword) =>
                $query->whereHas('category', fn ($category) => $category->where('name', 'like', "%{$keyword}%")));

        return $table->escapeColumns([])->toJson();
    }

    public function productionOrdersDt(CompanyContext $company, SubcontractSemiFinishedTableQuery $query): JsonResponse
    {
        abort_unless(auth()->user()->can('subcontract-semi-finished.create') || auth()->user()->can('subcontract-semi-finished.update'), 403);
        $table = DataTables::eloquent($query->productionOrders($company->id()))
            ->addColumn('actions', fn (ProductionOrder $order) => '<button type="button" class="btn btn-sm btn-primary js-select-semi-finished-mo" data-id="'.$order->id.'" data-number="'.e($order->number).'" data-buyer-po="'.e($order->buyer_po_number ?? '').'">'.e(__('ui.common.select')).'</button>')
            ->rawColumns(['actions']);
        foreach (SubcontractProductColumns::HEADINGS as $key => $label) {
            $table->addColumn('item_'.$key, fn (ProductionOrder $order) =>
                SubcontractProductColumns::values($order->outputProduct, $order->line_color, $order->line_size)[$key]);
        }

        return $table->toJson();
    }

    public function numberPreview(DocumentNumberService $numbers): JsonResponse { return response()->json(['number' => $numbers->preview('subcontract_semi_finished')]); }

    public function create(CompanyContext $company, DocumentNumberService $numbers): View
    {
        return $this->form($company, null, $numbers->preview('subcontract_semi_finished'));
    }

    public function edit(SubcontractSemiFinishedDocument $document, CompanyContext $company): View
    {
        abort_unless((int) $document->company_id === $company->id(), 404);

        return $this->form($company, $document, $document->number);
    }

    private function form(CompanyContext $company, ?SubcontractSemiFinishedDocument $document, string $number): View
    {
        $entry = $document ? $this->entry($document) : [];
        if (session()->hasOldInput('product_id')) {
            $productId = filter_var(old('product_id'), FILTER_VALIDATE_INT);
            $product = $productId ? app(SubcontractSemiFinishedTableQuery::class)->products()->find($productId) : null;
            $entry['product'] = $product ? $this->productData($product) : null;
        }
        if (array_key_exists('production_order_id', session()->getOldInput())) {
            $orderId = filter_var(old('production_order_id'), FILTER_VALIDATE_INT);
            $order = $orderId ? ProductionOrder::where('company_id', $company->id())->find($orderId, ['id', 'number']) : null;
            $entry['production_order_id'] = $order?->id;
            $entry['mo_number'] = $order?->number;
        }

        return view('super.subcontract-semi-finished.form', compact('document', 'entry', 'number'));
    }

    public function show(SubcontractSemiFinishedDocument $document, CompanyContext $company): JsonResponse
    {
        abort_unless((int) $document->company_id === $company->id(), 404);
        return response()->json($this->entry($document));
    }

    private function entry(SubcontractSemiFinishedDocument $document): array
    {
        $document->load(['items.product.unit:id,name,symbol', 'items.product.parentProduct:id,code', 'items.product.category:id,name', 'productionOrder:id,number,buyer_po_number']);
        $item = $document->items->first();
        return ['id' => $document->id, 'number' => $document->number, 'production_date' => $document->production_date?->toDateString(), 'production_order_id' => $document->production_order_id, 'mo_number' => $document->productionOrder?->number, 'buyer_po_number' => $document->buyer_po_number, 'description' => $document->description, 'product' => $item ? $this->productData($item->product, $item->product_id) : null, 'quantity' => $item?->quantity];
    }

    private function productData(?Product $product, ?int $productId = null): array
    {
        return [
            'id' => $productId ?? $product?->id,
            ...SubcontractProductColumns::values($product),
            'code' => $product?->code,
            'style' => $product?->style_code,
            'unit' => $product?->unit?->symbol ?: $product?->unit?->name,
        ];
    }

    public function store(Request $request, CompanyContext $company, SaveSubcontractSemiFinishedAction $action): JsonResponse|RedirectResponse
    {
        $data = $this->validated($request, $company);
        $document = $action->create($data, $company->id(), (int) $request->user()->id);
        if (!$request->expectsJson()) {
            return $this->redirectAfterSave($document);
        }

        return response()->json(['message' => __('ui.subcontract_semi_finished.saved'), 'id' => $document->id, 'number' => $document->number]);
    }

    public function update(Request $request, SubcontractSemiFinishedDocument $document, CompanyContext $company, SaveSubcontractSemiFinishedAction $action): JsonResponse|RedirectResponse
    {
        abort_unless((int) $document->company_id === $company->id(), 404);
        $action->update($document, $this->validated($request, $company), (int) $request->user()->id);
        if (!$request->expectsJson()) {
            return $this->redirectAfterSave($document);
        }

        return response()->json(['message' => __('ui.subcontract_semi_finished.saved')]);
    }

    private function redirectAfterSave(SubcontractSemiFinishedDocument $document): RedirectResponse
    {
        $date = $document->production_date->toDateString();

        return redirect()->route('super.subcontract.semi-finished.index', [
            'from_date' => min(now()->startOfMonth()->toDateString(), $date),
            'to_date' => max(now()->toDateString(), $date),
        ])->with('semi_finished_success', __('ui.subcontract_semi_finished.saved'));
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

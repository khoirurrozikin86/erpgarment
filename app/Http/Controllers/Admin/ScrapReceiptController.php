<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Actions\SaveScrapReceiptAction;
use App\Domain\Inventory\Queries\ScrapReceiptTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\ScrapReceiptsExport;
use App\Http\Controllers\Controller;
use App\Models\{Product, ProductionOrder, ScrapReceiptDocument};
use Illuminate\Http\{JsonResponse, RedirectResponse, Request};
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class ScrapReceiptController extends Controller
{
    public function index(): View { return view('super.scrap-receipts.index'); }

    public function create(DocumentNumberService $numbers): View
    {
        return $this->form(null, $numbers->preview('scrap_receipt'));
    }

    public function edit(ScrapReceiptDocument $scrapReceipt, CompanyContext $company): View
    {
        abort_unless((int) $scrapReceipt->company_id === $company->id(), 404);

        return $this->form($scrapReceipt, $scrapReceipt->number);
    }

    private function form(?ScrapReceiptDocument $document, string $number): View
    {
        $scrapProduct = $this->selectedProduct('scrap_product_id', $document?->scrap_product_id);
        $sourceProduct = $this->selectedProduct('source_product_id', $document?->source_product_id);

        return view('super.scrap-receipts.form', compact('document', 'number', 'scrapProduct', 'sourceProduct'));
    }

    private function selectedProduct(string $field, ?int $default): ?Product
    {
        $id = filter_var(old($field, $default), FILTER_VALIDATE_INT);

        return $id ? Product::with('unit')->find($id) : null;
    }

    public function numberPreview(DocumentNumberService $numbers): JsonResponse
    {
        return response()->json(['number' => $numbers->preview('scrap_receipt')]);
    }

    public function dt(Request $request, CompanyContext $company, ScrapReceiptTableQuery $query): JsonResponse
    {
        $filters = $this->filters($request);
        return DataTables::eloquent($query->builder($company->id(), $filters['from_date'] ?? now()->startOfMonth()->toDateString(), $filters['to_date'] ?? now()->toDateString()))
            ->addColumn('date_label', fn (ScrapReceiptDocument $row) => $row->receipt_date?->format('Y-m-d'))
            ->addColumn('scrap_code', fn (ScrapReceiptDocument $row) => $row->scrapProduct?->code ?? '—')
            ->filterColumn('scrap_code', fn ($query, $keyword) => $query->whereHas('scrapProduct', fn ($product) => $product->where('code', 'like', "%{$keyword}%")))
            ->addColumn('scrap_name', fn (ScrapReceiptDocument $row) => $row->scrapProduct?->name ?? '—')
            ->filterColumn('scrap_name', fn ($query, $keyword) => $query->whereHas('scrapProduct', fn ($product) => $product->where('name', 'like', "%{$keyword}%")))
            ->addColumn('unit_label', fn (ScrapReceiptDocument $row) => $row->scrapProduct?->unit?->symbol ?: $row->scrapProduct?->unit?->name ?: '—')
            ->addColumn('source_code', fn (ScrapReceiptDocument $row) => $row->sourceProduct?->code ?? '—')
            ->filterColumn('source_code', fn ($query, $keyword) => $query->whereHas('sourceProduct', fn ($product) => $product->where('code', 'like', "%{$keyword}%")))
            ->addColumn('source_name', fn (ScrapReceiptDocument $row) => $row->sourceProduct?->name ?? '—')
            ->filterColumn('source_name', fn ($query, $keyword) => $query->whereHas('sourceProduct', fn ($product) => $product->where('name', 'like', "%{$keyword}%")))
            ->addColumn('actions', function (ScrapReceiptDocument $row) {
                $items = '';
                if (auth()->user()->can('scrap-receipts.update')) {
                    $items .= '<li><a class="dropdown-item" href="'.e(route('super.scrap-receipts.edit', $row)).'">'.e(__('ui.common.edit')).'</a></li>';
                }
                if (auth()->user()->can('scrap-receipts.delete')) {
                    $items .= '<li><button type="button" class="dropdown-item text-danger btn-delete-scrap-receipt" data-url="'.e(route('super.scrap-receipts.destroy', $row)).'">'.e(__('ui.common.delete')).'</button></li>';
                }
                return $items === '' ? '' : '<div class="dropdown"><button class="btn btn-sm bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown"><i data-feather="more-vertical" class="icon-sm"></i></button><ul class="dropdown-menu dropdown-menu-end">'.$items.'</ul></div>';
            })->rawColumns(['actions'])->toJson();
    }

    public function productsDt(Request $request, ScrapReceiptTableQuery $query): JsonResponse
    {
        $products = $request->string('type')->value() === 'scrap' ? $query->scrapProducts() : $query->sourceProducts();
        return DataTables::eloquent($products)
            ->addColumn('unit_label', fn ($product) => $product->unit?->symbol ?: $product->unit?->name ?: '—')
            ->addColumn('actions', fn ($product) => '<button type="button" class="btn btn-sm btn-primary btn-select-scrap-product" data-id="'.(int)$product->id.'" data-code="'.e($product->code).'" data-name="'.e($product->name).'" data-unit="'.e($product->unit?->symbol ?: $product->unit?->name ?: '').'"><i data-feather="check" class="icon-sm"></i></button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function productionOrdersDt(CompanyContext $company): JsonResponse
    {
        $orders = ProductionOrder::query()->where('company_id', $company->id())
            ->whereNotNull('number')->where('number', '<>', '')->select('number')->distinct();

        return DataTables::eloquent($orders)->escapeColumns([])->toJson();
    }

    public function store(Request $request, SaveScrapReceiptAction $action): JsonResponse|RedirectResponse
    {
        $row = $action->create($this->validated($request), (int) $request->user()->id);

        return $this->saved($request, $row, __('ui.scrap_receipt.saved'), 201);
    }

    public function update(Request $request, ScrapReceiptDocument $scrapReceipt, SaveScrapReceiptAction $action): JsonResponse|RedirectResponse
    {
        $row = $action->update($scrapReceipt, $this->validated($request), (int) $request->user()->id);

        return $this->saved($request, $row, __('ui.scrap_receipt.updated'), 200);
    }

    private function saved(Request $request, ScrapReceiptDocument $row, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'number' => $row->number], $status);
        }

        $date = $row->receipt_date->toDateString();

        return redirect()->route('super.scrap-receipts.index', [
            'from_date' => min(now()->startOfMonth()->toDateString(), $date),
            'to_date' => max(now()->toDateString(), $date),
        ])->with('scrap_success', $message.' ('.$row->number.')');
    }

    public function destroy(Request $request, ScrapReceiptDocument $scrapReceipt, SaveScrapReceiptAction $action): JsonResponse
    {
        $action->delete($scrapReceipt, (int)$request->user()->id);
        return response()->json(['message' => __('ui.scrap_receipt.deleted')]);
    }

    public function export(Request $request, CompanyContext $company)
    {
        $filters = $this->filters($request);
        return Excel::download(new ScrapReceiptsExport($company->id(), $filters['from_date'] ?? now()->startOfMonth()->toDateString(), $filters['to_date'] ?? now()->toDateString()), 'scrap-receipts.xlsx');
    }

    private function filters(Request $request): array
    {
        return $request->validate(['from_date' => ['nullable', 'date'], 'to_date' => ['nullable', 'date', 'after_or_equal:from_date']]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'receipt_date' => ['required', 'date'],
            'scrap_product_id' => ['required', 'integer', 'exists:products,id'],
            'source_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'production_number' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}

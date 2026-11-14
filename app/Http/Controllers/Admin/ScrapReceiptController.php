<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Actions\SaveScrapReceiptAction;
use App\Domain\Inventory\Queries\ScrapReceiptTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\ScrapReceiptsExport;
use App\Http\Controllers\Controller;
use App\Models\ScrapReceiptDocument;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class ScrapReceiptController extends Controller
{
    public function index(): View { return view('super.scrap-receipts.index'); }

    public function numberPreview(DocumentNumberService $numbers): JsonResponse
    {
        return response()->json(['number' => $numbers->preview('scrap_receipt')]);
    }

    public function dt(Request $request, CompanyContext $company, ScrapReceiptTableQuery $query): JsonResponse
    {
        $filters = $this->filters($request);
        return DataTables::eloquent($query->builder($company->id(), $filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('date_label', fn (ScrapReceiptDocument $row) => $row->receipt_date?->format('Y-m-d'))
            ->addColumn('scrap_code', fn (ScrapReceiptDocument $row) => $row->scrapProduct?->code ?? '—')
            ->addColumn('scrap_name', fn (ScrapReceiptDocument $row) => $row->scrapProduct?->name ?? '—')
            ->addColumn('unit_label', fn (ScrapReceiptDocument $row) => $row->scrapProduct?->unit?->symbol ?: $row->scrapProduct?->unit?->name ?: '—')
            ->addColumn('source_code', fn (ScrapReceiptDocument $row) => $row->sourceProduct?->code ?? '—')
            ->addColumn('source_name', fn (ScrapReceiptDocument $row) => $row->sourceProduct?->name ?? '—')
            ->addColumn('actions', function (ScrapReceiptDocument $row) {
                $items = '';
                if (auth()->user()->can('scrap-receipts.update')) {
                    $items .= '<li><button type="button" class="dropdown-item btn-edit-scrap-receipt" data-id="'.(int)$row->id.'" data-number="'.e($row->number).'" data-date="'.$row->receipt_date?->format('Y-m-d').'" data-scrap="'.(int)$row->scrap_product_id.'" data-scrap-code="'.e($row->scrapProduct?->code).'" data-scrap-name="'.e($row->scrapProduct?->name).'" data-scrap-unit="'.e($row->scrapProduct?->unit?->symbol ?: $row->scrapProduct?->unit?->name ?: '').'" data-source="'.(int)$row->source_product_id.'" data-source-code="'.e($row->sourceProduct?->code).'" data-source-name="'.e($row->sourceProduct?->name).'" data-source-unit="'.e($row->sourceProduct?->unit?->symbol ?: $row->sourceProduct?->unit?->name ?: '').'" data-production="'.e($row->production_number).'" data-quantity="'.e($row->quantity).'" data-description="'.e($row->description).'">'.e(__('ui.common.edit')).'</button></li>';
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

    public function store(Request $request, SaveScrapReceiptAction $action): JsonResponse
    {
        $row = $action->create($this->validated($request), (int)$request->user()->id);
        return response()->json(['message' => __('ui.scrap_receipt.saved'), 'number' => $row->number], 201);
    }

    public function update(Request $request, ScrapReceiptDocument $scrapReceipt, SaveScrapReceiptAction $action): JsonResponse
    {
        $row = $action->update($scrapReceipt, $this->validated($request), (int)$request->user()->id);
        return response()->json(['message' => __('ui.scrap_receipt.updated'), 'number' => $row->number]);
    }

    public function destroy(Request $request, ScrapReceiptDocument $scrapReceipt, SaveScrapReceiptAction $action): JsonResponse
    {
        $action->delete($scrapReceipt, (int)$request->user()->id);
        return response()->json(['message' => __('ui.scrap_receipt.deleted')]);
    }

    public function export(Request $request, CompanyContext $company)
    {
        $filters = $this->filters($request);
        return Excel::download(new ScrapReceiptsExport($company->id(), $filters['from_date'] ?? null, $filters['to_date'] ?? null), 'scrap-receipts.xlsx');
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
            'source_product_id' => ['required', 'integer', 'exists:products,id'],
            'production_number' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}

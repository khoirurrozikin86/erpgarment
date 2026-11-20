<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Inventory\Queries\MaterialIssueTableQuery;
use App\Domain\Inventory\Services\MaterialIssueStockSku;
use App\Domain\Inventory\Services\MaterialIssueService;
use App\Exports\MaterialIssuesExport;
use App\Http\Controllers\Controller;
use App\Models\{MaterialIssueDocument, MaterialIssueItem, RawMaterialOrderDocument, RawMaterialOrderItem};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class MaterialIssueController extends Controller
{
    public function index(): View { return view('super.material-issues.index'); }

    public function requestsDt(CompanyContext $company, MaterialIssueTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->productionRequests($company->id()))
            ->addColumn('mo_number', fn (RawMaterialOrderDocument $document) => $document->items
                ->map(fn ($item) => $item->sourceMaterial?->productionOrder?->number)
                ->filter()->unique()->values()->implode(', ') ?: $document->production_order_number ?: '—')
            ->addColumn('request_number', fn (RawMaterialOrderDocument $document) => $document->number)
            ->addColumn('request_date', fn (RawMaterialOrderDocument $document) => $document->order_date?->format('Y-m-d'))
            ->addColumn('mis_number', fn (RawMaterialOrderDocument $document) => $this->latestMis($document)?->number ?: '—')
            ->addColumn('mis_date', fn (RawMaterialOrderDocument $document) => $this->latestMis($document)?->issue_date?->format('Y-m-d') ?: '—')
            ->addColumn('actions', function (RawMaterialOrderDocument $document) {
                $complete = $document->items->isNotEmpty() && $document->items->every(fn ($item) => (float) $item->issued_quantity >= (float) $item->quantity);
                $class = $complete ? 'btn-danger' : 'btn-primary';
                return '<a class="btn btn-sm '.$class.'" href="'.e(route('super.material-issues.requests.show', $document)).'"><i data-feather="'.($complete ? 'check' : 'send').'" class="icon-sm me-1"></i>'.e(__('ui.workflow.view_details')).'</a>';
            })
            ->rawColumns(['actions'])->toJson();
    }

    private function latestMis(RawMaterialOrderDocument $document): ?MaterialIssueDocument
    {
        return $document->items->pluck('latestMaterialIssueItem.document')->filter()
            ->sortByDesc('issue_date')->first();
    }

    public function historyDt(Request $request, CompanyContext $company, MaterialIssueTableQuery $query): JsonResponse
    {
        $filters = $this->dateFilters($request);
        return DataTables::eloquent($query->historyDocuments($company->id(), $filters['from_date'] ?? now()->startOfMonth()->toDateString(), $filters['to_date'] ?? now()->toDateString()))
            ->addColumn('issue_date', fn (MaterialIssueDocument $document) => $document->issue_date?->format('Y-m-d'))
            ->addColumn('request_number', fn (MaterialIssueDocument $document) => $document->rawMaterialOrder?->number ?: $document->request?->number ?: '—')
            ->addColumn('mo_number', function (MaterialIssueDocument $document) {
                $mo = $document->items
                    ->map(fn ($item) => $item->rawMaterialOrderItem?->sourceMaterial?->productionOrder?->number)
                    ->filter()->unique()->values()->implode(', ');
                return $mo ?: $document->rawMaterialOrder?->production_order_number ?: $document->request?->productionOrder?->number ?: '—';
            })
            ->addColumn('actions', function (MaterialIssueDocument $document) {
                $buttons = '<div class="d-inline-flex align-items-center justify-content-end gap-2 text-nowrap">'
                    . '<a class="btn btn-link btn-sm p-0 text-secondary" href="'.e(route('super.material-issues.documents.show', $document)).'" title="'.e(__('ui.workflow.view_details')).'" aria-label="'.e(__('ui.workflow.view_details')).'"><i data-feather="eye" class="icon-sm"></i></a>'
                    . '<a class="btn btn-link btn-sm p-0 text-primary" href="'.e(route('super.material-issues.documents.pdf', $document)).'" target="_blank" rel="noopener" title="'.e(__('ui.workflow.view_pdf')).'" aria-label="'.e(__('ui.workflow.view_pdf')).'"><i data-feather="file-text" class="icon-sm"></i></a>'
                    . '<a class="btn btn-link btn-sm p-0 text-secondary" href="'.e(route('super.material-issues.documents.pdf.download', $document)).'" title="'.e(__('ui.workflow.download_pdf')).'" aria-label="'.e(__('ui.workflow.download_pdf')).'"><i data-feather="download" class="icon-sm"></i></a>';
                return $buttons.'</div>';
            })
            ->rawColumns(['actions'])->toJson();
    }

    public function requestDetails(Request $request, RawMaterialOrderDocument $rawMaterialOrderDocument, CompanyContext $company, MaterialIssueStockSku $skuResolver): JsonResponse|View
    {
        $document = RawMaterialOrderDocument::query()->where('company_id', $company->id())
            ->with(['items.sourceMaterial.productionOrder', 'items.product.unit', 'items.product.variants', 'items.product.parentProduct.variants'])
            ->findOrFail($rawMaterialOrderDocument->id);
        $items = $document->items->sortBy('id');
        $details = [
            'id' => $document->id,
            'request_number' => $document->number,
            'order_number' => $document->number,
            'request_date' => $document->order_date?->format('Y-m-d'),
            'mo_number' => $items->map(fn ($line) => $line->sourceMaterial?->productionOrder?->number)->filter()->unique()->values()->implode(', ') ?: $document->production_order_number,
            'style' => $items->pluck('style_code')->filter()->unique()->values()->implode(', '),
            'items' => $items->map(function (RawMaterialOrderItem $line) use ($document, $skuResolver) {
                $product = $line->product;
                $sku = $skuResolver->resolve($line, $product, $line->sourceMaterial);

                return [
                    'id' => $line->id, 'request_number' => $document->number, 'product_id' => $line->product_id,
                    'code' => $product?->code ?? '—', 'name' => $product?->name ?? '—',
                    'parent_code' => $product?->parentProduct?->code ?? $product?->code ?? '—',
                    'sku_code' => $sku?->code,
                    'unit' => $line->product?->unit?->symbol ?: $line->product?->unit?->name ?: '—',
                    'style' => $line->style_code ?: '—',
                    'color' => $line->color ?: $line->sourceMaterial?->color ?: $line->product?->color ?: '—',
                    'size' => $line->size ?: $line->sourceMaterial?->size ?: $line->product?->size ?: '—',
                    'mo_number' => $line->sourceMaterial?->productionOrder?->number ?: $document->production_order_number,
                    'requested' => (float) $line->quantity, 'sent_before' => (float) $line->issued_quantity,
                    'remaining' => max(0, round((float) $line->quantity - (float) $line->issued_quantity, 4)),
                ];
            })->values(),
        ];

        if ($request->expectsJson()) {
            return response()->json($details);
        }

        $details['skipped_codes'] = $details['items']->whereNull('sku_code')->where('remaining', '>', 0)
            ->pluck('parent_code')->unique()->implode(', ');
        $details['items'] = $this->groupItems($details['items']->whereNotNull('sku_code'), ['requested', 'sent_before', 'remaining']);
        return view('super.material-issues.request', ['details' => $details]);
    }

    public function documentDetails(Request $request, int $document, CompanyContext $company): JsonResponse|View
    {
        $doc = MaterialIssueDocument::query()->where('company_id', $company->id())
            ->with(['request.productionOrder', 'rawMaterialOrder', 'items.product.unit', 'items.product.parentProduct:id,code', 'items.rawMaterialOrderItem.sourceMaterial.productionOrder', 'items.requestItem:id,color,size'])
            ->findOrFail($document);
        $details = [
            'number' => $doc->number, 'issue_date' => $doc->issue_date?->format('Y-m-d'),
            'request_number' => $doc->rawMaterialOrder?->number ?: $doc->request?->number ?: '—',
            'mo_number' => $doc->items->first()?->rawMaterialOrderItem?->sourceMaterial?->productionOrder?->number ?: $doc->rawMaterialOrder?->production_order_number ?: $doc->request?->productionOrder?->number,
            'items' => $doc->items->map(fn ($item) => [
                'code' => $item->product?->code, 'name' => $item->product?->name,
                'parent_code' => $item->product?->parentProduct?->code ?? $item->product?->code,
                'sku_code' => $item->product?->code,
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name,
                'color' => $item->issued_color ?? '—',
                'size' => $item->issued_size ?? '—',
                'quantity' => (float) $item->quantity,
            ]),
        ];

        if ($request->expectsJson()) {
            return response()->json($details);
        }

        $details['items'] = $this->groupItems($details['items'], ['quantity']);
        return view('super.material-issues.document', ['details' => $details, 'documentId' => $doc->id]);
    }

    private function groupItems(Collection $items, array $quantityFields): Collection
    {
        return $items->groupBy(fn (array $line) => json_encode(array_map(
            fn ($value) => mb_strtolower(trim($value ?? '')),
            [$line['sku_code'] ?? $line['code'], $line['color'], $line['size'], $line['unit']],
        )))->map(function (Collection $lines) use ($quantityFields) {
            $group = $lines->first();
            foreach ($quantityFields as $field) {
                $group[$field] = round($lines->sum($field), 4);
            }
            $group['sources'] = $lines->values();

            return $group;
        })->sortBy([['parent_code', 'asc'], ['color', 'asc'], ['size', 'asc'], ['sku_code', 'asc']])->values();
    }

    public function pdf(int $document, CompanyContext $company, CompanyPdfBrandingService $branding)
    {
        $issue = MaterialIssueDocument::query()->where('company_id', $company->id())
            ->with(['company', 'creator', 'rawMaterialOrder', 'items.product.unit', 'items.product.parentProduct:id,code', 'items.rawMaterialOrderItem.sourceMaterial.productionOrder', 'items.requestItem:id,color,size'])
            ->findOrFail($document);
        return Pdf::loadView('super.material-issues.pdf', [...$branding->data(), 'issue' => $issue])
            ->setPaper('a4')->stream($this->pdfFilename($issue->number));
    }

    public function downloadPdf(int $document, CompanyContext $company, CompanyPdfBrandingService $branding)
    {
        $issue = MaterialIssueDocument::query()->where('company_id', $company->id())
            ->with(['company', 'creator', 'rawMaterialOrder', 'items.product.unit', 'items.product.parentProduct:id,code', 'items.rawMaterialOrderItem.sourceMaterial.productionOrder', 'items.requestItem:id,color,size'])
            ->findOrFail($document);
        return Pdf::loadView('super.material-issues.pdf', [...$branding->data(), 'issue' => $issue])
            ->setPaper('a4')->download($this->pdfFilename($issue->number));
    }

    public function issue(Request $request, RawMaterialOrderItem $rawMaterialOrderItem, CompanyContext $company, MaterialIssueService $service): JsonResponse
    {
        abort_unless($rawMaterialOrderItem->company_id === $company->id(), 404);
        $data = $request->validate([
            'issue_date' => ['required', 'date_format:Y-m-d'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
        ]);
        $document = $service->issue($rawMaterialOrderItem, (float) $data['quantity'], $data['issue_date'], (int) $request->user()->id);
        return response()->json(['message' => __('ui.material_issue.saved'), 'number' => $document->number]);
    }

    public function issueBulk(Request $request, MaterialIssueService $service): JsonResponse
    {
        $data = $request->validate([
            'issue_date' => ['required', 'date_format:Y-m-d'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.raw_material_order_item_id' => ['required', 'integer', 'distinct', 'exists:raw_material_order_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
        ]);
        $document = $service->issueMany($data['items'], $data['issue_date'], (int) $request->user()->id);
        return response()->json(['message' => __('ui.material_issue.saved'), 'number' => $document->number, 'items_count' => $document->items->count()]);
    }

    public function updateItem(Request $request, MaterialIssueItem $materialIssueItem, MaterialIssueService $service): JsonResponse
    {
        $data = $request->validate([
            'issue_date' => ['required', 'date_format:Y-m-d'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
        ]);
        $item = $service->updateItem($materialIssueItem, (float) $data['quantity'], $data['issue_date'], (int) $request->user()->id);
        return response()->json(['message' => __('ui.material_issue.updated'), 'quantity' => (float) $item->quantity]);
    }

    public function destroyItem(Request $request, MaterialIssueItem $materialIssueItem, MaterialIssueService $service): JsonResponse
    {
        $service->deleteItem($materialIssueItem, (int) $request->user()->id);
        return response()->json(['message' => __('ui.material_issue.deleted')]);
    }

    public function export(Request $request, CompanyContext $company)
    {
        $filters = $this->dateFilters($request);
        return Excel::download(new MaterialIssuesExport($company->id(), $filters['from_date'] ?? now()->startOfMonth()->toDateString(), $filters['to_date'] ?? now()->toDateString()), 'material-issues.xlsx');
    }

    private function dateFilters(Request $request): array
    {
        return $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) $fail(__('ui.material_issue.invalid_date_range'));
            }],
        ]);
    }

    private function pdfFilename(string $number): string
    {
        return 'MIS-' . preg_replace('/[^A-Za-z0-9._-]+/', '-', $number) . '.pdf';
    }
}

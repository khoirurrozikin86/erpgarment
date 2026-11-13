<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Inventory\Queries\MaterialIssueTableQuery;
use App\Domain\Inventory\Services\MaterialIssueService;
use App\Exports\MaterialIssuesExport;
use App\Http\Controllers\Controller;
use App\Models\{MaterialIssueDocument, MaterialIssueItem, RawMaterialOrderDocument, RawMaterialOrderItem};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\{JsonResponse, Request};
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
                if (!request()->user()?->can('material-issues.create')) return '—';
                $complete = $document->items->isNotEmpty() && $document->items->every(fn ($item) => (float) $item->issued_quantity >= (float) $item->quantity);
                $class = $complete ? 'btn-danger' : 'btn-primary';
                return '<button type="button" class="btn btn-sm '.$class.' btn-open-material-issue" data-url="'.e(route('super.material-issues.requests.show', $document)).'"><i data-feather="'.($complete ? 'check' : 'send').'" class="icon-sm me-1"></i>'.e(__('ui.workflow.view_details')).'</button>';
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
        return DataTables::eloquent($query->history($company->id(), $filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('number', fn (MaterialIssueItem $item) => $item->document?->number)
            ->addColumn('issue_date', fn (MaterialIssueItem $item) => $item->document?->issue_date?->format('Y-m-d'))
            ->addColumn('request_number', fn (MaterialIssueItem $item) => $item->rawMaterialOrderItem?->number ?: $item->document?->request?->number ?: '—')
            ->addColumn('mo_number', fn (MaterialIssueItem $item) => $item->rawMaterialOrderItem?->sourceMaterial?->productionOrder?->number ?: $item->document?->rawMaterialOrder?->production_order_number ?: $item->document?->request?->productionOrder?->number ?: '—')
            ->addColumn('code', fn (MaterialIssueItem $item) => $item->product?->code)
            ->addColumn('name', fn (MaterialIssueItem $item) => $item->product?->name)
            ->addColumn('unit', fn (MaterialIssueItem $item) => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—')
            ->addColumn('actions', function (MaterialIssueItem $item) {
                $buttons = '<div class="d-inline-flex align-items-center justify-content-end gap-2 text-nowrap">'
                    . '<button type="button" class="btn btn-link btn-sm p-0 text-secondary btn-view-material-issue" data-url="'.e(route('super.material-issues.documents.show', $item->material_issue_document_id)).'" title="'.e(__('ui.workflow.view_details')).'" aria-label="'.e(__('ui.workflow.view_details')).'"><i data-feather="eye" class="icon-sm"></i></button>'
                    . '<a class="btn btn-link btn-sm p-0 text-primary" href="'.e(route('super.material-issues.documents.pdf', $item->material_issue_document_id)).'" target="_blank" rel="noopener" title="'.e(__('ui.workflow.view_pdf')).'" aria-label="'.e(__('ui.workflow.view_pdf')).'"><i data-feather="file-text" class="icon-sm"></i></a>'
                    . '<a class="btn btn-link btn-sm p-0 text-secondary" href="'.e(route('super.material-issues.documents.pdf.download', $item->material_issue_document_id)).'" title="'.e(__('ui.workflow.download_pdf')).'" aria-label="'.e(__('ui.workflow.download_pdf')).'"><i data-feather="download" class="icon-sm"></i></a>';
                if ($item->raw_material_order_item_id && request()->user()?->can('material-issues.update')) {
                    $buttons .= '<button type="button" class="btn btn-link btn-sm p-0 text-primary btn-edit-material-issue" data-url="'.e(route('super.material-issues.items.update', $item)).'" data-number="'.e($item->document?->number).'" data-date="'.e($item->document?->issue_date?->format('Y-m-d')).'" data-quantity="'.e($item->quantity).'" title="'.e(__('ui.common.edit')).'" aria-label="'.e(__('ui.common.edit')).'"><i data-feather="edit-2" class="icon-sm"></i></button>';
                }
                if ($item->raw_material_order_item_id && request()->user()?->can('material-issues.delete')) {
                    $buttons .= '<button type="button" class="btn btn-link btn-sm p-0 text-danger btn-delete-material-issue" data-url="'.e(route('super.material-issues.items.destroy', $item)).'" data-number="'.e($item->document?->number).'" title="'.e(__('ui.common.delete')).'" aria-label="'.e(__('ui.common.delete')).'"><i data-feather="trash-2" class="icon-sm"></i></button>';
                }
                return $buttons.'</div>';
            })
            ->rawColumns(['actions'])->toJson();
    }

    public function requestDetails(RawMaterialOrderDocument $rawMaterialOrderDocument, CompanyContext $company): JsonResponse
    {
        $document = RawMaterialOrderDocument::query()->where('company_id', $company->id())
            ->with(['items.document', 'items.sourceMaterial.productionOrder', 'items.product.unit'])
            ->findOrFail($rawMaterialOrderDocument->id);
        $items = $document->items->sortBy('id');
        return response()->json([
            'id' => $document->id,
            'request_number' => $document->number,
            'order_number' => $document->number,
            'request_date' => $document->order_date?->format('Y-m-d'),
            'mo_number' => $items->map(fn ($line) => $line->sourceMaterial?->productionOrder?->number)->filter()->unique()->values()->implode(', ') ?: $document->production_order_number,
            'style' => $items->pluck('style_code')->filter()->unique()->values()->implode(', '),
            'items' => $items->map(fn (RawMaterialOrderItem $line) => [
                'id' => $line->id, 'request_number' => $line->number, 'product_id' => $line->product_id,
                'code' => $line->product?->code ?? '—', 'name' => $line->product?->name ?? '—',
                'unit' => $line->product?->unit?->symbol ?: $line->product?->unit?->name ?: '—',
                'style' => $line->style_code ?: '—',
                'mo_number' => $line->sourceMaterial?->productionOrder?->number ?: $line->document?->production_order_number,
                'requested' => (float) $line->quantity, 'sent_before' => (float) $line->issued_quantity,
                'remaining' => max(0, (float) $line->quantity - (float) $line->issued_quantity),
            ]),
        ]);
    }

    public function documentDetails(int $document, CompanyContext $company): JsonResponse
    {
        $doc = MaterialIssueDocument::query()->where('company_id', $company->id())
            ->with(['request.productionOrder', 'rawMaterialOrder', 'items.product.unit', 'items.rawMaterialOrderItem.sourceMaterial.productionOrder'])
            ->findOrFail($document);
        return response()->json([
            'number' => $doc->number, 'issue_date' => $doc->issue_date?->format('Y-m-d'),
            'request_number' => $doc->items->first()?->rawMaterialOrderItem?->number ?: $doc->request?->number ?: '—',
            'mo_number' => $doc->items->first()?->rawMaterialOrderItem?->sourceMaterial?->productionOrder?->number ?: $doc->rawMaterialOrder?->production_order_number ?: $doc->request?->productionOrder?->number,
            'items' => $doc->items->map(fn ($item) => [
                'code' => $item->product?->code, 'name' => $item->product?->name,
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name,
                'quantity' => (float) $item->quantity,
            ]),
        ]);
    }

    public function pdf(int $document, CompanyContext $company, CompanyPdfBrandingService $branding)
    {
        $issue = MaterialIssueDocument::query()->where('company_id', $company->id())
            ->with(['company', 'creator', 'rawMaterialOrder', 'items.product.unit', 'items.rawMaterialOrderItem.sourceMaterial.productionOrder'])
            ->findOrFail($document);
        return Pdf::loadView('super.material-issues.pdf', [...$branding->data(), 'issue' => $issue])
            ->setPaper('a4')->stream($this->pdfFilename($issue->number));
    }

    public function downloadPdf(int $document, CompanyContext $company, CompanyPdfBrandingService $branding)
    {
        $issue = MaterialIssueDocument::query()->where('company_id', $company->id())
            ->with(['company', 'creator', 'rawMaterialOrder', 'items.product.unit', 'items.rawMaterialOrderItem.sourceMaterial.productionOrder'])
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
        return Excel::download(new MaterialIssuesExport($company->id(), $filters['from_date'] ?? null, $filters['to_date'] ?? null), 'material-issues.xlsx');
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

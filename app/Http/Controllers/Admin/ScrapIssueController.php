<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Actions\SaveScrapIssueAction;
use App\Domain\Inventory\Queries\ScrapIssueTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\ScrapIssuesExport;
use App\Http\Controllers\Controller;
use App\Models\{Customer, Product, ScrapIssueDocument};
use Illuminate\Http\{JsonResponse, RedirectResponse, Request};
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class ScrapIssueController extends Controller
{
    public function index(): View { return view('super.scrap-issues.index'); }
    public function create(DocumentNumberService $numbers): View
    {
        return $this->form(null, $numbers->preview('scrap_issue'));
    }

    public function edit(ScrapIssueDocument $scrapIssue, CompanyContext $company): View
    {
        abort_unless((int) $scrapIssue->company_id === $company->id(), 404);

        return $this->form($scrapIssue, $scrapIssue->number);
    }

    private function form(?ScrapIssueDocument $document, string $number): View
    {
        $scrapProduct = $this->selectedProduct('scrap_product_id', $document?->scrap_product_id);
        $customerId = filter_var(old('customer_id', $document?->customer_id), FILTER_VALIDATE_INT);
        $customer = $customerId ? Customer::find($customerId) : null;

        return view('super.scrap-issues.form', compact('document', 'number', 'scrapProduct', 'customer'));
    }

    private function selectedProduct(string $field, ?int $default): ?Product
    {
        $id = filter_var(old($field, $default), FILTER_VALIDATE_INT);

        return $id ? Product::with('unit')->find($id) : null;
    }

    public function numberPreview(DocumentNumberService $numbers): JsonResponse { return response()->json(['number' => $numbers->preview('scrap_issue')]); }

    public function dt(Request $request, CompanyContext $company, ScrapIssueTableQuery $query): JsonResponse
    {
        $filters = $this->filters($request);
        return DataTables::eloquent($query->builder($company->id(), $filters['from_date'] ?? now()->startOfMonth()->toDateString(), $filters['to_date'] ?? now()->toDateString()))
            ->addColumn('date_label', fn (ScrapIssueDocument $row) => $row->issue_date?->format('Y-m-d'))
            ->addColumn('customer_name', fn (ScrapIssueDocument $row) => $row->customer?->name ?? '—')
            ->filterColumn('customer_name', fn ($q, $keyword) => $q->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$keyword}%")))
            ->addColumn('scrap_code', fn (ScrapIssueDocument $row) => $row->scrapProduct?->code ?? '—')
            ->filterColumn('scrap_code', fn ($q, $keyword) => $q->whereHas('scrapProduct', fn ($p) => $p->where('code', 'like', "%{$keyword}%")))
            ->addColumn('scrap_name', fn (ScrapIssueDocument $row) => $row->scrapProduct?->name ?? '—')
            ->filterColumn('scrap_name', fn ($q, $keyword) => $q->whereHas('scrapProduct', fn ($p) => $p->where('name', 'like', "%{$keyword}%")))
            ->addColumn('unit_label', fn (ScrapIssueDocument $row) => $row->scrapProduct?->unit?->symbol ?: $row->scrapProduct?->unit?->name ?: '—')
            ->addColumn('actions', function (ScrapIssueDocument $row) {
                $items = '';
                if (auth()->user()->can('scrap-issues.update')) $items .= '<li><a class="dropdown-item" href="'.e(route('super.scrap-issues.edit', $row)).'">'.e(__('ui.common.edit')).'</a></li>';
                if (auth()->user()->can('scrap-issues.delete')) $items .= '<li><button type="button" class="dropdown-item text-danger btn-delete-scrap-issue" data-url="'.e(route('super.scrap-issues.destroy', $row)).'">'.e(__('ui.common.delete')).'</button></li>';
                return $items === '' ? '' : '<div class="dropdown"><button class="btn btn-sm bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown"><i data-feather="more-vertical" class="icon-sm"></i></button><ul class="dropdown-menu dropdown-menu-end">'.$items.'</ul></div>';
            })->rawColumns(['actions'])->toJson();
    }

    public function productsDt(ScrapIssueTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->scrapProducts())->addColumn('unit_label', fn ($p) => $p->unit?->symbol ?: $p->unit?->name ?: '—')
            ->addColumn('actions', fn ($p) => '<button type="button" class="btn btn-sm btn-primary btn-select-scrap-issue-product" data-id="'.(int)$p->id.'" data-code="'.e($p->code).'" data-name="'.e($p->name).'" data-unit="'.e($p->unit?->symbol ?: $p->unit?->name ?: '').'"><i data-feather="check" class="icon-sm"></i></button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function customersDt(ScrapIssueTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->customers())->addColumn('actions', fn ($c) => '<button type="button" class="btn btn-sm btn-primary btn-select-scrap-issue-customer" data-id="'.(int)$c->id.'" data-name="'.e($c->name).'"><i data-feather="check" class="icon-sm"></i></button>')->rawColumns(['actions'])->toJson();
    }

    public function store(Request $request, SaveScrapIssueAction $action): JsonResponse|RedirectResponse
    {
        $row = $action->create($this->validated($request), (int) $request->user()->id);

        return $this->saved($request, $row, __('ui.scrap_issue.saved'), 201);
    }

    public function update(Request $request, ScrapIssueDocument $scrapIssue, SaveScrapIssueAction $action): JsonResponse|RedirectResponse
    {
        $row = $action->update($scrapIssue, $this->validated($request), (int) $request->user()->id);

        return $this->saved($request, $row, __('ui.scrap_issue.updated'), 200);
    }

    private function saved(Request $request, ScrapIssueDocument $row, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'number' => $row->number], $status);
        }

        $date = $row->issue_date->toDateString();

        return redirect()->route('super.scrap-issues.index', [
            'from_date' => min(now()->startOfMonth()->toDateString(), $date),
            'to_date' => max(now()->toDateString(), $date),
        ])->with('scrap_success', $message.' ('.$row->number.')');
    }

    public function destroy(Request $request, ScrapIssueDocument $scrapIssue, SaveScrapIssueAction $action): JsonResponse { $action->delete($scrapIssue, (int)$request->user()->id); return response()->json(['message' => __('ui.scrap_issue.deleted')]); }
    public function export(Request $request, CompanyContext $company) { $f = $this->filters($request); return Excel::download(new ScrapIssuesExport($company->id(), $f['from_date'] ?? now()->startOfMonth()->toDateString(), $f['to_date'] ?? now()->toDateString()), 'scrap-issues.xlsx'); }
    private function filters(Request $r): array { return $r->validate(['from_date' => ['nullable', 'date'], 'to_date' => ['nullable', 'date', 'after_or_equal:from_date']]); }
    private function validated(Request $r): array { return $r->validate(['issue_date' => ['required', 'date'], 'customer_id' => ['required', 'integer', 'exists:customers,id'], 'scrap_product_id' => ['required', 'integer', 'exists:products,id'], 'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'], 'description' => ['nullable', 'string', 'max:5000']]); }
}

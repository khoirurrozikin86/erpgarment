<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Actions\SaveWipIssueAction;
use App\Domain\Inventory\Queries\WipIssueTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\WipIssuesExport;
use App\Http\Controllers\Controller;
use App\Models\{Product, WipIssueLine};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class WipIssueController extends Controller
{
    public function index(): View { return view('super.wip-issues.index'); }
    public function numberPreview(DocumentNumberService $numbers): JsonResponse { return response()->json(['number' => $numbers->preview('wip_issue')]); }

    public function dt(Request $request, CompanyContext $company, WipIssueTableQuery $query): JsonResponse
    {
        $filters = $this->filters($request);
        return DataTables::eloquent($query->builder($company->id(), $filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('number', fn (WipIssueLine $line) => $line->document?->number)
            ->addColumn('transaction_date', fn (WipIssueLine $line) => $line->document?->transaction_date?->format('Y-m-d'))
            ->addColumn('code', fn (WipIssueLine $line) => $line->product?->code)
            ->addColumn('name', fn (WipIssueLine $line) => $line->product?->name)
            ->addColumn('unit', fn (WipIssueLine $line) => $line->product?->unit?->symbol ?: $line->product?->unit?->name ?: '—')
            ->addColumn('style', fn (WipIssueLine $line) => $line->product?->style_code ?: '—')
            ->addColumn('creator', fn (WipIssueLine $line) => $line->document?->creator?->name ?: '—')
            ->addColumn('actions', function (WipIssueLine $line) {
                $links = '';
                if (auth()->user()->can('wip-issues.update')) $links .= '<li><button type="button" class="dropdown-item btn-edit-wip-issue" data-id="'.(int)$line->id.'" data-number="'.e($line->document?->number).'" data-date="'.$line->document?->transaction_date?->format('Y-m-d').'" data-product="'.(int)$line->product_id.'" data-code="'.e($line->product?->code).'" data-name="'.e($line->product?->name).'" data-unit="'.e($line->product?->unit?->symbol ?: $line->product?->unit?->name ?: '').'" data-quantity="'.e($line->quantity).'" data-description="'.e($line->description).'">'.e(__('ui.common.edit')).'</button></li>';
                if (auth()->user()->can('wip-issues.delete')) $links .= '<li><button type="button" class="dropdown-item text-danger btn-delete-wip-issue" data-url="'.e(route('super.wip-issues.destroy', $line)).'" data-number="'.e($line->document?->number).'">'.e(__('ui.common.delete')).'</button></li>';
                return $links ? '<div class="dropdown"><button class="btn btn-sm btn-link p-0" data-bs-toggle="dropdown"><i data-feather="more-vertical" class="icon-sm"></i></button><ul class="dropdown-menu dropdown-menu-end">'.$links.'</ul></div>' : '';
            })->rawColumns(['actions'])->toJson();
    }

    public function productsDt(WipIssueTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->products()->with('category:id,name,code'))
            ->addColumn('unit_label', fn (Product $product) => $product->unit?->symbol ?: $product->unit?->name ?: '—')
            ->addColumn('category_label', fn (Product $product) => $product->category?->name ?: '—')
            ->addColumn('actions', fn (Product $product) => '<button type="button" class="btn btn-sm btn-primary btn-select-wip-product" data-id="'.(int)$product->id.'" data-code="'.e($product->code).'" data-name="'.e($product->name).'" data-unit="'.e($product->unit?->symbol ?: $product->unit?->name ?: '').'"><i data-feather="check" class="icon-sm"></i></button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function export(Request $request, CompanyContext $company)
    {
        $filters = $this->filters($request);
        return Excel::download(new WipIssuesExport($company->id(), $filters['from_date'] ?? null, $filters['to_date'] ?? null), 'wip-issues.xlsx');
    }

    public function store(Request $request, SaveWipIssueAction $action): JsonResponse
    {
        $line = $action->create($this->validated($request), (int)$request->user()->id);
        return response()->json(['message' => __('ui.wip_issue.saved'), 'number' => $line->document?->number], 201);
    }

    public function update(Request $request, WipIssueLine $wipIssueLine, CompanyContext $company, SaveWipIssueAction $action): JsonResponse
    {
        abort_unless($wipIssueLine->document()->where('company_id', $company->id())->exists(), 404);
        $action->update($wipIssueLine, $this->validated($request), (int)$request->user()->id);
        return response()->json(['message' => __('ui.wip_issue.updated')]);
    }

    public function destroy(Request $request, WipIssueLine $wipIssueLine, CompanyContext $company, SaveWipIssueAction $action): JsonResponse
    {
        abort_unless($wipIssueLine->document()->where('company_id', $company->id())->exists(), 404);
        $action->delete($wipIssueLine, (int)$request->user()->id);
        return response()->json(['message' => __('ui.wip_issue.deleted')]);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) $fail(__('ui.wip_issue.invalid_range'));
            }],
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'transaction_date' => ['required', 'date_format:Y-m-d'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}

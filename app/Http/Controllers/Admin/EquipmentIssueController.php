<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Actions\SaveEquipmentIssueAction;
use App\Domain\Inventory\Queries\EquipmentIssueTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\EquipmentIssuesExport;
use App\Http\Controllers\Controller;
use App\Models\EquipmentIssueDocument;
use Illuminate\Http\{JsonResponse, Request};
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class EquipmentIssueController extends Controller
{
    public function index() { return view('super.equipment-issues.index'); }
    public function numberPreview(DocumentNumberService $numbers): JsonResponse { return response()->json(['number' => $numbers->preview('equipment_issue')]); }

    public function dt(Request $request, CompanyContext $company, EquipmentIssueTableQuery $query): JsonResponse
    {
        $filters = $this->filters($request);
        return DataTables::eloquent($query->documents($company->id(), $filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('date_label', fn ($r) => $r->issue_date?->format('Y-m-d'))
            ->addColumn('item_code', fn ($r) => $r->item?->item_code ?? '—')->addColumn('item_name', fn ($r) => $r->item?->item_name ?? '—')
            ->addColumn('unit', fn ($r) => $r->item?->unit ?? '—')->addColumn('quantity', fn ($r) => $r->item?->quantity ?? '—')->addColumn('description', fn ($r) => $r->item?->description ?? '—')
            ->addColumn('actions', function ($r) {
                $html = '<div class="d-flex gap-1">';
                if (auth()->user()->can('equipment-issues.update')) $html .= '<button class="btn btn-sm btn-outline-primary btn-edit-equipment-issue" data-id="'.(int)$r->id.'" title="'.e(__('ui.common.edit')).'"><i data-feather="edit-2" class="icon-sm"></i></button>';
                if (auth()->user()->can('equipment-issues.delete')) $html .= '<button class="btn btn-sm btn-outline-danger btn-delete-equipment-issue" data-id="'.(int)$r->id.'" title="'.e(__('ui.common.delete')).'"><i data-feather="trash-2" class="icon-sm"></i></button>';
                return $html.'</div>';
            })->rawColumns(['actions'])->toJson();
    }

    public function productsDt(EquipmentIssueTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->products())->addColumn('category_label', fn ($p) => $p->category?->name ?? '—')
            ->addColumn('unit_label', fn ($p) => $p->unit?->symbol ?: $p->unit?->name ?: '—')
            ->addColumn('actions', fn ($p) => '<button type="button" class="btn btn-sm btn-primary btn-select-equipment-issue-product" data-id="'.(int)$p->id.'" data-code="'.e($p->code).'" data-name="'.e($p->name).'" data-unit="'.e($p->unit?->symbol ?: $p->unit?->name ?: '').'">'.e(__('ui.common.select')).'</button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function show(Request $request, EquipmentIssueDocument $equipmentIssue, CompanyContext $company): JsonResponse
    {
        abort_unless($equipmentIssue->company_id === $company->id(), 404);
        return response()->json($equipmentIssue->load('item')->toArray());
    }

    public function store(Request $request, SaveEquipmentIssueAction $action): JsonResponse { $r = $action->create($this->validated($request), (int)$request->user()->id); return response()->json(['message' => __('ui.machine_issue.saved'), 'number' => $r->number], 201); }
    public function update(Request $request, EquipmentIssueDocument $equipmentIssue, SaveEquipmentIssueAction $action): JsonResponse { $r = $action->update($equipmentIssue, $this->validated($request), (int)$request->user()->id); return response()->json(['message' => __('ui.machine_issue.updated'), 'number' => $r->number]); }
    public function destroy(Request $request, EquipmentIssueDocument $equipmentIssue, SaveEquipmentIssueAction $action): JsonResponse { $action->delete($equipmentIssue, (int)$request->user()->id); return response()->json(['message' => __('ui.machine_issue.deleted')]); }
    public function export(Request $request, CompanyContext $company) { $f = $this->filters($request); return Excel::download(new EquipmentIssuesExport($company->id(), $f['from_date'] ?? null, $f['to_date'] ?? null), 'equipment-issues.xlsx'); }

    private function filters(Request $r): array { return $r->validate(['from_date' => ['nullable', 'date'], 'to_date' => ['nullable', 'date', 'after_or_equal:from_date']]); }
    private function validated(Request $r): array { return $r->validate(['issue_date' => ['required', 'date'], 'product_id' => ['required', 'integer', 'exists:products,id'], 'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'], 'description' => ['nullable', 'string', 'max:5000']]); }
}

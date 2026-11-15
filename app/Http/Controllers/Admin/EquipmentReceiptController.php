<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Actions\SaveEquipmentReceiptAction;
use App\Domain\Inventory\Queries\EquipmentReceiptTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\EquipmentReceiptsExport;
use App\Http\Controllers\Controller;
use App\Models\EquipmentReceiptDocument;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class EquipmentReceiptController extends Controller
{
    public function index(): View { return view('super.equipment-receipts.index'); }
    public function numberPreview(DocumentNumberService $numbers): JsonResponse { return response()->json(['number' => $numbers->preview('equipment_receipt')]); }

    public function dt(Request $request, CompanyContext $company, EquipmentReceiptTableQuery $query): JsonResponse
    {
        $filters = $this->filters($request);
        return DataTables::eloquent($query->documents($company->id(), $filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('date_label', fn (EquipmentReceiptDocument $r) => $r->receipt_date?->format('Y-m-d'))
            ->addColumn('invoice_date_label', fn (EquipmentReceiptDocument $r) => $r->invoice_date?->format('Y-m-d') ?? '—')
            ->addColumn('ownership_label', fn (EquipmentReceiptDocument $r) => __('ui.machine_receipt.ownership_'.$r->ownership_type))
            ->addColumn('supplier_name', fn (EquipmentReceiptDocument $r) => $r->supplier?->name ?? '—')
            ->addColumn('actions', function (EquipmentReceiptDocument $r) {
                $items = '<li><button type="button" class="dropdown-item btn-detail-equipment" data-id="'.(int)$r->id.'">'.e(__('ui.common.detail')).'</button></li>';
                if (auth()->user()->can('equipment-receipts.update')) $items .= '<li><button type="button" class="dropdown-item btn-edit-equipment" data-id="'.(int)$r->id.'">'.e(__('ui.common.edit')).'</button></li>';
                if (auth()->user()->can('equipment-receipts.delete')) $items .= '<li><button type="button" class="dropdown-item text-danger btn-delete-equipment" data-id="'.(int)$r->id.'">'.e(__('ui.common.delete')).'</button></li>';
                return '<div class="dropdown"><button class="btn btn-sm bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown"><i data-feather="more-vertical" class="icon-sm"></i></button><ul class="dropdown-menu dropdown-menu-end">'.$items.'</ul></div>';
            })->rawColumns(['actions'])->toJson();
    }

    public function productsDt(EquipmentReceiptTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->products()->with('category:id,name'))
            ->addColumn('unit_label', fn ($p) => $p->unit?->symbol ?: $p->unit?->name ?: '—')
            ->addColumn('category_label', fn ($p) => $p->category?->name ?? '—')
            ->addColumn('actions', fn ($p) => '<button type="button" class="btn btn-sm btn-primary btn-select-equipment-product" data-id="'.(int)$p->id.'" data-code="'.e($p->code).'" data-name="'.e($p->name).'" data-unit="'.e($p->unit?->symbol ?: $p->unit?->name ?: '').'" data-price="'.e($p->purchase_price).'">'.e(__('ui.common.select')).'</button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function suppliersDt(EquipmentReceiptTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->suppliers())->addColumn('actions', fn ($s) => '<button type="button" class="btn btn-sm btn-primary btn-select-equipment-supplier" data-id="'.(int)$s->id.'" data-name="'.e($s->name).'">'.e(__('ui.common.select')).'</button>')->rawColumns(['actions'])->toJson();
    }

    public function show(Request $request, EquipmentReceiptDocument $equipmentReceipt, CompanyContext $company): JsonResponse
    {
        abort_unless($equipmentReceipt->company_id === $company->id(), 404);
        $equipmentReceipt->load(['supplier:id,name', 'items']);
        return response()->json($equipmentReceipt->toArray() + ['ownership_label' => __('ui.machine_receipt.ownership_'.$equipmentReceipt->ownership_type)]);
    }

    public function store(Request $request, SaveEquipmentReceiptAction $action): JsonResponse { $r = $action->create($this->validated($request), (int)$request->user()->id); return response()->json(['message' => __('ui.machine_receipt.saved'), 'number' => $r->number], 201); }
    public function update(Request $request, EquipmentReceiptDocument $equipmentReceipt, SaveEquipmentReceiptAction $action): JsonResponse { $r = $action->update($equipmentReceipt, $this->validated($request), (int)$request->user()->id); return response()->json(['message' => __('ui.machine_receipt.updated'), 'number' => $r->number]); }
    public function destroy(Request $request, EquipmentReceiptDocument $equipmentReceipt, SaveEquipmentReceiptAction $action): JsonResponse { $action->delete($equipmentReceipt, (int)$request->user()->id); return response()->json(['message' => __('ui.machine_receipt.deleted')]); }
    public function export(Request $request, CompanyContext $company) { $f = $this->filters($request); return Excel::download(new EquipmentReceiptsExport($company->id(), $f['from_date'] ?? null, $f['to_date'] ?? null), 'equipment-receipts.xlsx'); }
    private function filters(Request $r): array { return $r->validate(['from_date' => ['nullable', 'date'], 'to_date' => ['nullable', 'date', 'after_or_equal:from_date']]); }
    private function validated(Request $r): array { return $r->validate(['receipt_date' => ['required', 'date'], 'ownership_type' => ['required', 'in:own,rent'], 'invoice_number' => ['nullable', 'string', 'max:150'], 'invoice_date' => ['nullable', 'date'], 'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'], 'currency_code' => ['nullable', 'string', 'max:10'], 'description' => ['nullable', 'string', 'max:5000'], 'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'integer', 'exists:products,id'], 'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'], 'items.*.unit_price' => ['nullable', 'numeric', 'gte:0', 'max:999999999999.9999'], 'items.*.supplier_po_number' => ['nullable', 'string', 'max:150'], 'items.*.description' => ['nullable', 'string', 'max:5000']]); }
}

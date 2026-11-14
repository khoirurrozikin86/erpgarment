<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Actions\SaveFinishedGoodsAction;
use App\Domain\Manufacturing\Queries\FinishedGoodsTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\FinishedGoodsExport;
use App\Http\Controllers\Controller;
use App\Models\ProductionOrderStageEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class FinishedGoodsController extends Controller
{
    public function index() { return view('super.finished-goods.index'); }

    private function filters(Request $request): array
    {
        return $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) $fail(__('ui.finished_goods.invalid_range'));
            }],
        ]);
    }

    public function dt(Request $request, CompanyContext $company, FinishedGoodsTableQuery $query): JsonResponse
    {
        $filters = $this->filters($request);
        return DataTables::eloquent($query->builder($company->id(), $filters['from_date'] ?? null, $filters['to_date'] ?? null))->addIndexColumn()
            ->addColumn('date', fn ($row) => $row->processed_at?->format('Y-m-d'))
            ->addColumn('style', fn ($row) => $row->productionOrder?->outputProduct?->style_code ?: '—')
            ->addColumn('item_code', fn ($row) => $row->productionOrder?->outputProduct?->code)
            ->addColumn('item_name', fn ($row) => $row->productionOrder?->outputProduct?->name)
            ->addColumn('mo', fn ($row) => $row->productionOrder?->number)
            ->addColumn('po_buyer', fn ($row) => $row->productionOrder?->buyer_po_number ?: '—')
            ->addColumn('actions', function (ProductionOrderStageEntry $row) {
                $buttons = '';
                if (auth()->user()->can('finished-goods.update')) $buttons .= '<button class="btn btn-outline-primary btn-sm p-1 js-edit-finished" title="'.e(__('ui.common.edit')).'" data-id="'.$row->id.'" data-no="'.e($row->finished_goods_number).'" data-date="'.$row->processed_at?->format('Y-m-d').'" data-mo="'.$row->production_order_id.'" data-mo-number="'.e($row->productionOrder?->number).'" data-style="'.e($row->productionOrder?->outputProduct?->style_code).'" data-code="'.e($row->productionOrder?->outputProduct?->code).'" data-name="'.e($row->productionOrder?->outputProduct?->name).'" data-unit="'.e($row->productionOrder?->outputProduct?->unit?->symbol ?: $row->productionOrder?->outputProduct?->unit?->name).'" data-po="'.e($row->productionOrder?->buyer_po_number).'" data-received="'.e($row->input_quantity).'" data-good="'.e($row->good_quantity).'"><i data-feather="edit-2" class="icon-sm"></i></button>';
                if (auth()->user()->can('finished-goods.delete')) $buttons .= '<button class="btn btn-outline-danger btn-sm p-1 js-delete-finished" title="'.e(__('ui.common.delete')).'" data-id="'.$row->id.'"><i data-feather="trash-2" class="icon-sm"></i></button>';
                return '<div class="d-flex gap-1">'.$buttons.'</div>';
            })->rawColumns(['actions'])->toJson();
    }

    public function ordersDt(CompanyContext $company, FinishedGoodsTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->eligibleOrders($company->id()))
            ->addColumn('style', fn ($order) => $order->outputProduct?->style_code ?: '—')
            ->addColumn('item_code', fn ($order) => $order->outputProduct?->code)
            ->addColumn('item_name', fn ($order) => $order->outputProduct?->name)
            ->addColumn('unit', fn ($order) => $order->outputProduct?->unit?->symbol ?: $order->outputProduct?->unit?->name ?: '—')
            ->addColumn('actions', fn ($order) => '<button type="button" class="btn btn-primary btn-sm js-select-finished-mo" data-id="'.$order->id.'" data-no="'.e($order->number).'" data-style="'.e($order->outputProduct?->style_code).'" data-code="'.e($order->outputProduct?->code).'" data-name="'.e($order->outputProduct?->name).'" data-unit="'.e($order->outputProduct?->unit?->symbol ?: $order->outputProduct?->unit?->name).'" data-po="'.e($order->buyer_po_number).'" data-available="'.e($order->available_packing_quantity).'">'.e(__('ui.common.select')).'</button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function preview(DocumentNumberService $numbers): JsonResponse { return response()->json(['number' => $numbers->preview('finished_goods')]); }
    public function export(Request $request, CompanyContext $company) { $filters = $this->filters($request); return Excel::download(new FinishedGoodsExport($company->id(), $filters['from_date'] ?? null, $filters['to_date'] ?? null), 'finished-goods.xlsx'); }
    public function store(Request $request, SaveFinishedGoodsAction $action): JsonResponse { $action->create($this->validated($request), (int) $request->user()->id); return response()->json(['message' => __('ui.finished_goods.saved')], 201); }

    public function update(Request $request, ProductionOrderStageEntry $entry, CompanyContext $company, SaveFinishedGoodsAction $action): JsonResponse
    {
        abort_unless($entry->stage === 'finishing' && $entry->productionOrder()->where('company_id', $company->id())->exists(), 404);
        $action->update($entry, $this->validated($request), (int) $request->user()->id);
        return response()->json(['message' => __('ui.finished_goods.updated')]);
    }

    public function destroy(Request $request, ProductionOrderStageEntry $entry, CompanyContext $company, SaveFinishedGoodsAction $action): JsonResponse
    {
        abort_unless($entry->stage === 'finishing' && $entry->productionOrder()->where('company_id', $company->id())->exists(), 404);
        $action->delete($entry, (int) $request->user()->id);
        return response()->json(['message' => __('ui.finished_goods.deleted')]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'production_order_id' => ['required', 'integer', 'exists:production_orders,id'],
            'finished_goods_date' => ['required', 'date_format:Y-m-d'],
            'receive_from_packing' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'finished_good_quantity' => ['required', 'numeric', 'min:0', 'max:999999999999.9999'],
        ]);
    }
}

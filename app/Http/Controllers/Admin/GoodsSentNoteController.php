<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Actions\SaveGoodsSentNoteAction;
use App\Domain\Manufacturing\Queries\GoodsSentNoteTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\GoodsSentNotesExport;
use App\Http\Controllers\Controller;
use App\Models\GoodsSentNote;
use Illuminate\Http\{JsonResponse, Request};
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class GoodsSentNoteController extends Controller
{
    public function index() { return view('super.goods-sent-notes.index'); }
    public function create() { return view('super.goods-sent-notes.form', ['note' => null]); }
    public function edit(GoodsSentNote $note, CompanyContext $company) { abort_unless($note->company_id === $company->id(), 404); return view('super.goods-sent-notes.form', compact('note')); }

    private function filters(Request $request): array
    {
        return $request->validate(['from_date' => ['nullable', 'date_format:Y-m-d'], 'to_date' => ['nullable', 'date_format:Y-m-d', function ($a, $v, $fail) use ($request) { if ($v && $request->input('from_date') && $v < $request->input('from_date')) $fail(__('ui.gsn.invalid_range')); }]]);
    }

    public function dt(Request $request, CompanyContext $company, GoodsSentNoteTableQuery $query): JsonResponse
    {
        $f = $this->filters($request);
        return DataTables::eloquent($query->builder($company->id(), $f['from_date'] ?? null, $f['to_date'] ?? null))->addIndexColumn()
            ->addColumn('mo', fn ($n) => $n->items->pluck('productionOrder.number')->filter()->unique()->implode(', '))
            ->addColumn('order_date', fn ($n) => $n->items->first()?->productionOrder?->po_date?->format('Y-m-d') ?: '—')
            ->addColumn('date', fn ($n) => $n->sent_date?->format('Y-m-d'))
            ->addColumn('user_input', fn ($n) => $n->creator?->username ?: $n->creator?->name ?: '—')
            ->addColumn('actions', function (GoodsSentNote $n) {
                $b = '<button class="btn btn-primary btn-sm py-0 px-1 js-show-gsn" data-id="'.$n->id.'">'.e(__('ui.common.detail')).'</button>';
                if (auth()->user()->can('goods-sent-notes.update')) $b .= ' <a class="btn btn-outline-primary btn-sm p-1" title="'.e(__('ui.common.edit')).'" href="'.route('super.goods-sent-notes.edit', $n).'"><i data-feather="edit-2" class="icon-sm"></i></a>';
                $b .= ' <a class="btn btn-outline-secondary btn-sm p-1" title="'.e(__('ui.common.print')).'" target="_blank" href="'.route('super.goods-sent-notes.print', $n).'"><i data-feather="printer" class="icon-sm"></i></a>';
                if (auth()->user()->can('goods-sent-notes.delete')) $b .= ' <button class="btn btn-outline-danger btn-sm p-1 js-delete-gsn" data-id="'.$n->id.'" title="'.e(__('ui.common.delete')).'"><i data-feather="trash-2" class="icon-sm"></i></button>';
                return '<div class="d-flex align-items-center gap-1 text-nowrap">'.$b.'</div>';
            })->rawColumns(['actions'])->toJson();
    }

    public function ordersDt(CompanyContext $company, GoodsSentNoteTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->eligibleOrders($company->id()))
            ->addColumn('style', fn ($o) => $o->outputProduct?->style_code ?: '—')
            ->addColumn('item_code', fn ($o) => $o->outputProduct?->code)
            ->addColumn('item_name', fn ($o) => $o->outputProduct?->name)
            ->addColumn('unit', fn ($o) => $o->outputProduct?->unit?->symbol ?: $o->outputProduct?->unit?->name ?: '—')
            ->addColumn('customer', fn ($o) => $o->buyer?->name ?: '—')
            ->addColumn('actions', fn ($o) => '<button type="button" class="btn btn-primary btn-sm js-choose-gsn-order" data-id="'.$o->id.'" data-mo="'.e($o->number).'" data-po="'.e($o->buyer_po_number).'" data-product="'.$o->output_product_id.'" data-style="'.e($o->outputProduct?->style_code).'" data-code="'.e($o->outputProduct?->code).'" data-name="'.e($o->outputProduct?->name).'" data-unit="'.e($o->outputProduct?->unit?->symbol ?: $o->outputProduct?->unit?->name).'" data-customer="'.e($o->customer_id).'" data-available="'.e($o->available_quantity).'">'.e(__('ui.common.select')).'</button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function preview(DocumentNumberService $numbers): JsonResponse { return response()->json(['number' => $numbers->preview('goods_sent_note')]); }
    public function store(Request $request, SaveGoodsSentNoteAction $action): JsonResponse { $action->create($this->validated($request), (int) $request->user()->id); return response()->json(['message' => __('ui.gsn.saved')], 201); }
    public function update(Request $request, GoodsSentNote $note, CompanyContext $company, SaveGoodsSentNoteAction $action): JsonResponse { abort_unless($note->company_id === $company->id(), 404); $action->update($note, $this->validated($request), (int) $request->user()->id); return response()->json(['message' => __('ui.gsn.updated')]); }
    public function destroy(Request $request, GoodsSentNote $note, CompanyContext $company, SaveGoodsSentNoteAction $action): JsonResponse { abort_unless($note->company_id === $company->id(), 404); $action->delete($note, (int) $request->user()->id); return response()->json(['message' => __('ui.gsn.deleted')]); }
    public function show(GoodsSentNote $note, CompanyContext $company): JsonResponse { abort_unless($note->company_id === $company->id(), 404); return response()->json($note->load(['items.productionOrder.outputProduct.unit', 'items.customer'])); }
    public function print(GoodsSentNote $note, CompanyContext $company) { abort_unless($note->company_id === $company->id(), 404); return view('super.goods-sent-notes.print', ['note' => $note->load(['items.productionOrder.outputProduct.unit', 'items.productionOrder.buyer', 'items.customer']), 'company' => $company->get()]); }
    public function export(Request $request, CompanyContext $company) { $f = $this->filters($request); return Excel::download(new GoodsSentNotesExport($company->id(), $f['from_date'] ?? null, $f['to_date'] ?? null), 'goods-sent-notes.xlsx'); }

    private function validated(Request $request): array
    {
        return $request->validate([
            'sent_date' => ['required', 'date_format:Y-m-d'], 'contract_number' => ['nullable', 'string', 'max:100'], 'contract_date' => ['nullable', 'date_format:Y-m-d'],
            'delivery_route' => ['nullable', 'string', 'max:100'], 'warehouse' => ['nullable', 'string', 'max:150'], 'vehicle_number' => ['nullable', 'string', 'max:100'],
            'driver_phone' => ['nullable', 'string', 'max:50'], 'driver_name' => ['nullable', 'string', 'max:150'], 'seal_number' => ['nullable', 'string', 'max:100'],
            'receiver_type' => ['nullable', 'string', 'max:100'], 'container_number' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'], 'items.*.production_order_id' => ['required', 'integer', 'distinct', 'exists:production_orders,id'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'], 'items.*.customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'], 'items.*.cartons' => ['nullable', 'numeric', 'min:0', 'max:999999999999.9999'],
        ]);
    }
}

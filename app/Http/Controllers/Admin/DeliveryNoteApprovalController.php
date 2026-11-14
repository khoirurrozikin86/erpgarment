<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Actions\SaveGoodsSentNoteAction;
use App\Domain\Manufacturing\Queries\GoodsSentNoteTableQuery;
use App\Http\Controllers\Controller;
use App\Models\GoodsSentNote;
use Illuminate\Http\{JsonResponse, Request};
use Yajra\DataTables\Facades\DataTables;

class DeliveryNoteApprovalController extends Controller
{
    public function index() { return view('super.delivery-note-approvals.index'); }

    private function filters(Request $request): array
    {
        return $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) $fail(__('ui.gsn.invalid_range'));
            }],
        ]);
    }

    public function dt(Request $request, CompanyContext $company, GoodsSentNoteTableQuery $query): JsonResponse
    {
        $filters = $this->filters($request);
        return DataTables::eloquent($query->approvals($company->id(), $filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addIndexColumn()
            ->addColumn('customer', fn (GoodsSentNote $note) => $note->items->map(fn ($item) => $item->customer?->name)->filter()->unique()->implode(', ') ?: '—')
            ->addColumn('po_buyer', fn (GoodsSentNote $note) => $note->items->map(fn ($item) => $item->productionOrder?->buyer_po_number)->filter()->unique()->implode(', ') ?: '—')
            ->addColumn('approved', fn (GoodsSentNote $note) => $note->approved_at
                ? '<span class="badge bg-success">YES</span>'
                : '<span class="badge bg-danger">NO</span>')
            ->addColumn('approved_by_name', fn (GoodsSentNote $note) => $note->approver?->username ?: $note->approver?->name ?: '—')
            ->addColumn('approved_date', fn (GoodsSentNote $note) => $note->approved_at?->format('Y-m-d H:i:s') ?: '—')
            ->addColumn('actions', function (GoodsSentNote $note) {
                $buttons = '';
                if (!$note->approved_at && auth()->user()->can('goods-sent-approvals.approve')) {
                    $buttons .= '<button class="btn btn-primary btn-sm py-0 px-1 js-approve-delivery" data-id="'.$note->id.'">'.e(__('ui.delivery_note_approval.approve')).'</button> ';
                } elseif ($note->approved_at && auth()->user()->can('goods-sent-approvals.cancel')) {
                    $buttons .= '<button class="btn btn-outline-primary btn-sm py-0 px-1 js-cancel-delivery" data-id="'.$note->id.'">'.e(__('ui.delivery_note_approval.cancel')).'</button> ';
                }
                $buttons .= '<button class="btn btn-primary btn-sm py-0 px-1 js-delivery-detail" data-id="'.$note->id.'">'.e(__('ui.common.detail')).'</button>';
                return '<div class="d-flex align-items-center gap-1 text-nowrap">'.$buttons.'</div>';
            })
            ->editColumn('sent_date', fn (GoodsSentNote $note) => $note->sent_date?->format('Y-m-d'))
            ->rawColumns(['actions', 'approved'])->toJson();
    }

    public function show(GoodsSentNote $note, CompanyContext $company): JsonResponse
    {
        abort_unless($note->company_id === $company->id(), 404);
        return response()->json($note->load(['items.productionOrder:id,number,buyer_po_number,output_product_id', 'items.productionOrder.outputProduct:id,code,name,style_code,unit_id', 'items.productionOrder.outputProduct.unit:id,name,symbol', 'items.customer:id,name']));
    }

    public function approve(Request $request, GoodsSentNote $note, CompanyContext $company, SaveGoodsSentNoteAction $action): JsonResponse
    {
        abort_unless($note->company_id === $company->id(), 404);
        $action->approve($note, (int) $request->user()->id);
        return response()->json(['message' => __('ui.delivery_note_approval.approved')]);
    }

    public function cancel(Request $request, GoodsSentNote $note, CompanyContext $company, SaveGoodsSentNoteAction $action): JsonResponse
    {
        abort_unless($note->company_id === $company->id(), 404);
        $action->cancelApproval($note, (int) $request->user()->id);
        return response()->json(['message' => __('ui.delivery_note_approval.cancelled')]);
    }
}

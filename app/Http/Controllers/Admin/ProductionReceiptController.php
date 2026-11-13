<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Actions\ApproveWarehouseMaterialReceiptAction;
use App\Domain\Manufacturing\Queries\ProductionReceiptTableQuery;
use App\Http\Controllers\Controller;
use App\Models\MaterialIssueDocument;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ProductionReceiptController extends Controller
{
    public function index(): View
    {
        return view('super.production-receipts.index');
    }

    public function datatable(Request $request, CompanyContext $company, ProductionReceiptTableQuery $query): JsonResponse
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) $fail(__('ui.production_receipt.invalid_date_range'));
            }],
        ]);

        return DataTables::eloquent($query->forCompany($company->id(), $filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('approval_status', fn (MaterialIssueDocument $document) => $document->production_receipt_status === 'received'
                ? '<span class="badge bg-success">'.e(__('ui.production_receipt.received')).'</span>'
                : '<span class="badge bg-danger">'.e(__('ui.production_receipt.not_received')).'</span>')
            ->addColumn('order_number', fn (MaterialIssueDocument $document) => $document->rawMaterialOrder?->number ?: '—')
            ->addColumn('actions', function (MaterialIssueDocument $document) {
                $actions = '<div class="d-inline-flex align-items-center justify-content-end gap-2 text-nowrap">'
                    .'<button type="button" class="btn btn-link btn-sm p-0 text-primary btn-show-production-receipt" data-url="'.e(route('super.production-receipts.documents.show', $document)).'" title="'.e(__('ui.workflow.view_details')).'" aria-label="'.e(__('ui.workflow.view_details')).'"><i data-feather="eye" class="icon-sm"></i></button>';
                if ($document->production_receipt_status !== 'received' && request()->user()?->can('production-receipts.approve')) {
                    $actions .= '<button type="button" class="btn btn-link btn-sm p-0 text-success btn-approve-production-receipt" data-url="'.e(route('super.production-receipts.approve', $document)).'" data-number="'.e($document->number).'" title="'.e(__('ui.workflow.approve')).'" aria-label="'.e(__('ui.workflow.approve')).'"><i data-feather="check-circle" class="icon-sm"></i></button>';
                }
                return $actions.'</div>';
            })
            ->rawColumns(['approval_status', 'actions'])->toJson();
    }

    public function details(int $document, CompanyContext $company): JsonResponse
    {
        $receipt = MaterialIssueDocument::query()->where('company_id', $company->id())
            ->whereNotNull('raw_material_order_document_id')
            ->with(['rawMaterialOrder:id,number', 'productionReceiver:id,name', 'items.product.unit', 'items.rawMaterialOrderItem'])
            ->findOrFail($document);

        return response()->json([
            'number' => $receipt->number,
            'date' => $receipt->issue_date?->format('Y-m-d'),
            'order_number' => $receipt->rawMaterialOrder?->number ?: '—',
            'status' => $receipt->production_receipt_status,
            'received_by' => $receipt->productionReceiver?->name,
            'received_at' => $receipt->production_received_at?->format('d-m-Y H:i'),
            'items' => $receipt->items->map(fn ($item) => [
                'code' => $item->product?->code ?: '—',
                'name' => $item->product?->name ?: '—',
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'style' => $item->rawMaterialOrderItem?->style_code ?: '—',
                'purchase_order' => null,
                'quantity' => (float) $item->quantity,
            ]),
        ]);
    }

    public function approve(MaterialIssueDocument $document, CompanyContext $company, ApproveWarehouseMaterialReceiptAction $approve): JsonResponse
    {
        $approved = $approve($document, $company->id(), (int) request()->user()->id);
        return response()->json(['message' => __('ui.production_receipt.approved_message', ['number' => $approved->number])]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Purchasing\Actions\DeleteGoodsReceiptAction;
use App\Domain\Purchasing\Actions\ApproveGoodsReceiptAction;
use App\Domain\Purchasing\Queries\GoodsReceiptTableQuery;
use App\Domain\Purchasing\Services\GoodsReceiptService;
use App\Exports\GoodsReceiptsExport;
use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class GoodsReceiptController extends Controller
{
    public function index()
    {
        return view('super.goods-receipts.index');
    }

    public function create()
    {
        return view('super.goods-receipts.create');
    }

    public function dt(Request $request, GoodsReceiptTableQuery $query): JsonResponse
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) {
                    $fail('Tanggal akhir harus sama atau setelah tanggal awal.');
                }
            }],
        ]);

        return DataTables::eloquent($query->builder($filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('purchase_order_number', fn (GoodsReceipt $receipt) => $receipt->purchaseOrder?->number ?? '—')
            ->addColumn('supplier_name', fn (GoodsReceipt $receipt) => $receipt->supplier?->name ?? __('ui.workflow.deleted_supplier'))
            ->addColumn('material_request_number', fn (GoodsReceipt $receipt) => $receipt->purchaseOrder?->purchaseRequest?->materialRequest?->number ?? '—')
            ->addColumn('approval_status', fn (GoodsReceipt $receipt) => $receipt->status === 'approved'
                ? '<span class="badge bg-success">' . e(__('ui.workflow.approval_approved')) . '</span>'
                : '<span class="badge bg-warning text-dark">' . e(__('ui.workflow.approval_pending')) . '</span>')
            ->addColumn('actions', function (GoodsReceipt $receipt) {
                $buttons = '<div class="d-inline-flex align-items-center justify-content-end gap-2 text-nowrap">'
                    . '<button type="button" class="btn btn-link btn-sm p-0 text-secondary btn-view-goods-receipt" data-url="' . e(route('super.goods-receipts.show', $receipt)) . '" title="' . e(__('ui.workflow.view_details')) . '" aria-label="' . e(__('ui.workflow.view_details')) . '"><i data-feather="eye" class="icon-sm"></i></button>'
                    . '<a class="btn btn-link btn-sm p-0 text-primary" href="' . e(route('super.goods-receipts.pdf', $receipt)) . '" target="_blank" rel="noopener" title="' . e(__('ui.workflow.view_pdf')) . '" aria-label="' . e(__('ui.workflow.view_pdf')) . '"><i data-feather="file-text" class="icon-sm"></i></a>'
                    . '<a class="btn btn-link btn-sm p-0 text-secondary" href="' . e(route('super.goods-receipts.pdf.download', $receipt)) . '" title="' . e(__('ui.workflow.download_pdf')) . '" aria-label="' . e(__('ui.workflow.download_pdf')) . '"><i data-feather="download" class="icon-sm"></i></a>';
                if ($receipt->status === 'pending' && auth()->user()?->can('goods-receipts.approve')) {
                    $buttons .= '<button type="button" class="btn btn-link btn-sm p-0 text-success btn-approve-goods-receipt" data-url="' . e(route('super.goods-receipts.approve', $receipt)) . '" data-number="' . e($receipt->number) . '" title="' . e(__('ui.workflow.approve')) . '" aria-label="' . e(__('ui.workflow.approve')) . '"><i data-feather="check-circle" class="icon-sm"></i></button>';
                }
                if (auth()->user()?->can('goods-receipts.delete')) {
                    $buttons .= '<button type="button" class="btn btn-link btn-sm p-0 text-danger btn-delete-goods-receipt" data-url="' . e(route('super.goods-receipts.destroy', $receipt)) . '" data-number="' . e($receipt->number) . '" title="' . e(__('ui.common.delete')) . '" aria-label="' . e(__('ui.common.delete')) . '"><i data-feather="trash-2" class="icon-sm"></i></button>';
                }
                return $buttons . '</div>';
            })
            ->rawColumns(['actions', 'approval_status'])
            ->toJson();
    }

    public function export(Request $request)
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) $fail('Tanggal akhir harus sama atau setelah tanggal awal.');
            }],
        ]);
        $from = $filters['from_date'] ?? null;
        $to = $filters['to_date'] ?? null;

        return Excel::download(new GoodsReceiptsExport($from, $to), 'goods-receipts-' . ($from ?: 'all') . '-to-' . ($to ?: 'all') . '.xlsx');
    }

    public function eligible(GoodsReceiptTableQuery $query)
    {
        return DataTables::eloquent($query->eligiblePurchaseOrders())
            ->addColumn('supplier_name', fn (PurchaseOrder $order) => $order->supplier?->name ?? __('ui.workflow.deleted_supplier'))
            ->addColumn('actions', fn (PurchaseOrder $order) => '<button type="button" class="btn btn-sm btn-primary btn-select-receipt-po" data-id="' . (int) $order->id . '" data-number="' . e($order->number) . '"><i data-feather="check" class="icon-sm me-1"></i>' . e(__('ui.workflow.select_action')) . '</button>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function source(int $purchaseOrderId, GoodsReceiptTableQuery $query): JsonResponse
    {
        $order = $query->sourcePurchaseOrder($purchaseOrderId);

        return response()->json([
            'id' => $order->id,
            'number' => $order->number,
            'supplier' => $order->supplier?->name ?? __('ui.workflow.deleted_supplier'),
            'material_order_number' => $order->purchaseRequest?->materialRequest?->productionOrder?->number,
            'style_code' => $order->purchaseRequest?->materialRequest?->productionOrder?->outputProduct?->style_code
                ?? $order->purchaseRequest?->materialRequest?->outputProduct?->style_code,
            'currency' => $order->purchaseRequest?->materialRequest?->productionOrder?->currency ?: 'USD',
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'code' => $item->product?->code ?? '—',
                'name' => $item->product?->name ?? __('ui.workflow.deleted_item'),
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'ordered' => (float) $item->quantity,
                'received' => (float) $item->received_quantity,
                'remaining' => max(0, round((float) $item->quantity - (float) $item->received_quantity, 4)),
                'unit_price' => (float) $item->unit_price,
                'track_stock' => (bool) ($item->product?->track_stock ?? false),
            ])->filter(fn ($item) => $item['remaining'] > 0)->values(),
        ]);
    }

    public function store(Request $request, GoodsReceiptService $service): JsonResponse
    {
        $data = $request->validate([
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'received_at' => ['required', 'date', 'before_or_equal:now'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'invoice_date' => ['nullable', 'date_format:Y-m-d'],
            'currency' => ['required', 'string', 'max:10'],
            'customs_document_code' => ['nullable', 'string', 'max:30'],
            'customs_document_number' => ['nullable', 'string', 'max:100', 'required_with:customs_document_code'],
            'customs_document_date' => ['nullable', 'date_format:Y-m-d', 'required_with:customs_document_number'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'integer', 'exists:purchase_order_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0', 'max:999999999999.9999'],
            'items.*.lot_number' => ['nullable', 'string', 'max:100'],
        ]);

        $receipt = $service->receive($data, (int) $request->user()->id);

        return response()->json([
            'message' => __('ui.workflow.receipt_created_message', ['number' => $receipt->number]),
            'id' => $receipt->id,
        ], 201);
    }

    public function show(GoodsReceipt $goodsReceipt, GoodsReceiptTableQuery $query): JsonResponse
    {
        $receipt = $query->details($goodsReceipt);

        return response()->json([
            'number' => $receipt->number,
            'purchase_order' => $receipt->purchaseOrder?->number ?? '—',
            'material_order' => $receipt->purchaseOrder?->purchaseRequest?->materialRequest?->productionOrder?->number ?? '—',
            'style' => $receipt->purchaseOrder?->purchaseRequest?->materialRequest?->productionOrder?->outputProduct?->style_code
                ?? $receipt->purchaseOrder?->purchaseRequest?->materialRequest?->outputProduct?->style_code ?? '—',
            'invoice_number' => $receipt->invoice_number ?: '—',
            'invoice_date' => $receipt->invoice_date?->format('d/m/Y') ?? '—',
            'currency' => $receipt->currency ?: '—',
            'status_label' => $receipt->status === 'approved' ? __('ui.workflow.approval_approved') : __('ui.workflow.approval_pending'),
            'approver' => $receipt->approver?->name ?? '—',
            'approved_at' => $receipt->approved_at?->format('d/m/Y H:i') ?? '—',
            'supplier' => $receipt->supplier?->name ?? 'Supplier dihapus',
            'receiver' => $receipt->receiver?->name ?? 'User dihapus',
            'received_at' => $receipt->received_at?->format('d/m/Y H:i') ?? '—',
            'customs_document_code' => $receipt->customs_document_code ?: '—',
            'customs_document_number' => $receipt->customs_document_number ?: '—',
            'customs_document_date' => $receipt->customs_document_date?->format('d/m/Y') ?? '—',
            'notes' => $receipt->notes ?: '—',
            'items' => $receipt->items->map(fn ($item) => [
                'code' => $item->product?->code ?? '—',
                'name' => $item->product?->name ?? 'Barang dihapus',
                'quantity' => number_format((float) $item->quantity, 4, ',', '.'),
                'lot_number' => $item->inventoryLot?->lot_number ?? '—',
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ]),
        ]);
    }

    public function destroy(GoodsReceipt $goodsReceipt, DeleteGoodsReceiptAction $delete): JsonResponse
    {
        $delete($goodsReceipt, (int) request()->user()->id);

        return response()->json(['message' => __('ui.workflow.receipt_deleted')]);
    }

    public function approve(GoodsReceipt $goodsReceipt, ApproveGoodsReceiptAction $approve): JsonResponse
    {
        $receipt = $approve($goodsReceipt, (int) request()->user()->id);

        return response()->json([
            'message' => __('ui.workflow.receipt_approved_message', ['number' => $receipt->number]),
            'id' => $receipt->id,
        ]);
    }

    public function pdf(GoodsReceipt $goodsReceipt, GoodsReceiptTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $receipt = $query->details($goodsReceipt);

        return Pdf::loadView('super.goods-receipts.pdf', [
            ...$branding->data(),
            'receipt' => $receipt,
        ])->setPaper('a4')->stream($this->pdfFilename($receipt->number));
    }

    public function downloadPdf(GoodsReceipt $goodsReceipt, GoodsReceiptTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $receipt = $query->details($goodsReceipt);

        return Pdf::loadView('super.goods-receipts.pdf', [
            ...$branding->data(),
            'receipt' => $receipt,
        ])->setPaper('a4')->download($this->pdfFilename($receipt->number));
    }

    private function pdfFilename(string $number): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $number), '-_.') . '.pdf';
    }
}

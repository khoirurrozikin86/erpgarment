<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Purchasing\Queries\PurchaseOrderTableQuery;
use App\Domain\Purchasing\Services\PurchaseOrderService;
use App\Exports\PurchaseOrdersExport;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return view('super.purchase-orders.index');
    }

    public function export(Request $request)
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) {
                    $fail('Tanggal akhir harus sama atau setelah tanggal awal.');
                }
            }],
        ]);
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        return Excel::download(
            new PurchaseOrdersExport($fromDate, $toDate),
            'purchase-orders-' . ($fromDate ?: 'all') . '-to-' . ($toDate ?: 'all') . '.xlsx'
        );
    }

    public function create(Request $request, PurchaseOrderTableQuery $query)
    {
        $initialPurchaseRequest = null;
        if ($request->filled('purchase_request') && ctype_digit((string) $request->query('purchase_request'))) {
            $initialPurchaseRequest = $query->eligiblePurchaseRequests()
                ->whereKey((int) $request->query('purchase_request'))
                ->first(['id', 'number']);
        }

        return view('super.purchase-orders.create', compact('initialPurchaseRequest'));
    }

    public function dt(Request $request, PurchaseOrderTableQuery $query): JsonResponse
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
            ->addColumn('purchase_request_number', fn (PurchaseOrder $order) => $order->purchaseRequest?->number ?? '—')
            ->addColumn('supplier_name', fn (PurchaseOrder $order) => $order->supplier?->name ?? __('ui.workflow.deleted_supplier'))
            ->addColumn('creator_name', fn (PurchaseOrder $order) => $order->creator?->name ?? __('ui.workflow.deleted_user'))
            ->addColumn('production_style_quantity', function (PurchaseOrder $order) {
                $materialRequest = $order->purchaseRequest?->materialRequest;
                $productionOrder = $materialRequest?->productionOrder;
                $product = $productionOrder?->outputProduct ?? $materialRequest?->outputProduct;
                $quantity = $productionOrder?->planned_quantity ?? $materialRequest?->production_quantity;
                if (!$product?->style_code && $quantity === null) {
                    return '—';
                }
                $style = e($product?->style_code ?: '—');
                $qty = $quantity === null ? '—' : rtrim(rtrim(number_format((float) $quantity, 4, ',', '.'), '0'), ',');

                return '<span class="text-nowrap">' . $style . ' <span class="text-muted">/</span> ' . $qty . '</span>';
            })
            ->addColumn('requested_items', function (PurchaseOrder $order) {
                if ($order->items->isEmpty()) {
                    return '—';
                }
                $items = $order->items->map(function ($item) {
                    $code = e($item->product?->code ?? '—');
                    $name = e($item->product?->name ?? __('ui.workflow.deleted_item'));
                    $quantity = rtrim(rtrim(number_format((float) $item->quantity, 4, ',', '.'), '0'), ',');

                    return '<span class="badge rounded-pill bg-light text-secondary border fw-normal" title="' . $name . '">' . $code . ' × ' . $quantity . '</span>';
                })->implode(' ');

                return '<div class="d-flex flex-wrap gap-1" style="max-width:24rem;max-height:4.25rem;overflow:auto">' . $items . '</div>';
            })
            ->addColumn('status_label', fn (PurchaseOrder $order) => match ($order->status) {
                'issued' => '<span class="badge bg-primary">' . __('ui.workflow.issued') . '</span>',
                'partially_received' => '<span class="badge bg-info text-dark">' . __('ui.workflow.receive_partial') . '</span>',
                'received' => '<span class="badge bg-success">' . __('ui.workflow.received_complete') . '</span>',
                'cancelled' => '<span class="badge bg-danger">' . __('ui.common.cancelled') . '</span>',
                default => '<span class="badge bg-secondary">' . __('ui.workflow.draft') . '</span>',
            })
            ->addColumn('actions', function (PurchaseOrder $order) {
                $buttons = '<div class="d-inline-flex align-items-center justify-content-end gap-2 text-nowrap">'
                    . '<button type="button" class="btn btn-link btn-sm p-0 text-secondary btn-view-purchase-order" data-url="' . e(route('super.purchase-orders.show', $order)) . '" title="' . e(__('ui.workflow.view_detail')) . '" aria-label="' . e(__('ui.workflow.view_detail')) . '"><i data-feather="eye" class="icon-sm"></i></button>';
                $buttons .= '<button type="button" class="btn btn-link btn-sm p-0 text-secondary btn-email-history" data-url="' . e(route('super.purchase-orders.emails', $order)) . '" title="' . e(__('ui.workflow.po_email_history')) . '" aria-label="' . e(__('ui.workflow.po_email_history')) . '"><i data-feather="clock" class="icon-sm"></i></button>';
                $buttons .= '<a class="btn btn-link btn-sm p-0 text-primary" href="' . e(route('super.purchase-orders.pdf', $order)) . '" target="_blank" rel="noopener" title="' . e(__('ui.workflow.view_pdf')) . '" aria-label="' . e(__('ui.workflow.view_pdf')) . '"><i data-feather="file-text" class="icon-sm"></i></a>';
                $buttons .= '<a class="btn btn-link btn-sm p-0 text-secondary" href="' . e(route('super.purchase-orders.pdf.download', $order)) . '" title="' . e(__('ui.workflow.download_pdf')) . '" aria-label="' . e(__('ui.workflow.download_pdf')) . '"><i data-feather="download" class="icon-sm"></i></a>';
                if ($order->status === 'draft' && auth()->user()->can('purchase-orders.issue')) {
                    $buttons .= '<button type="button" class="btn btn-link btn-sm p-0 text-success btn-issue-purchase-order" data-url="' . e(route('super.purchase-orders.issue', $order)) . '" title="' . e(__('ui.workflow.issue_po')) . '" aria-label="' . e(__('ui.workflow.issue_po')) . '"><i data-feather="send" class="icon-sm"></i></button>';
                }
                if (in_array($order->status, ['issued', 'partially_received', 'received'], true) && auth()->user()->can('purchase-orders.email')) {
                    $buttons .= '<button type="button" class="btn btn-link btn-sm p-0 text-primary btn-email-purchase-order" data-url="' . e(route('super.purchase-orders.email', $order)) . '" data-email="' . e($order->supplier?->email ?? '') . '" title="' . e(__('ui.workflow.email_po')) . '" aria-label="' . e(__('ui.workflow.email_po')) . '"><i data-feather="mail" class="icon-sm"></i></button>';
                }
                return $buttons . '</div>';
            })
            ->rawColumns(['status_label', 'production_style_quantity', 'requested_items', 'actions'])
            ->toJson();
    }

    public function eligible(PurchaseOrderTableQuery $query)
    {
        return DataTables::eloquent($query->eligiblePurchaseRequests())
            ->addColumn('supplier_name', fn (PurchaseRequest $request) => $request->supplier?->name ?? __('ui.workflow.deleted_supplier'))
            ->addColumn('requester_name', fn (PurchaseRequest $request) => $request->requester?->name ?? __('ui.workflow.deleted_user'))
            ->addColumn('actions', fn (PurchaseRequest $request) => '<button type="button" class="btn btn-sm btn-primary btn-select-purchase-request" data-id="' . (int) $request->id . '" data-number="' . e($request->number) . '"><i data-feather="check" class="icon-sm me-1"></i>' . e(__('ui.workflow.select_action')) . '</button>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function source(int $purchaseRequestId, PurchaseOrderTableQuery $query): JsonResponse
    {
        $purchaseRequest = $query->sourcePurchaseRequest($purchaseRequestId);
        return response()->json([
            'id' => $purchaseRequest->id,
            'number' => $purchaseRequest->number,
            'supplier' => $purchaseRequest->supplier?->name ?? __('ui.workflow.deleted_supplier'),
            'items' => $purchaseRequest->items->map(fn ($item) => [
                'code' => $item->product?->code ?? '—',
                'name' => $item->product?->name ?? __('ui.workflow.deleted_item'),
                'quantity' => $item->quantity,
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ]),
        ]);
    }

    public function store(Request $request, PurchaseOrderService $service): JsonResponse
    {
        $data = $request->validate([
            'purchase_request_id' => ['required', 'integer', 'exists:purchase_requests,id'],
            'expected_delivery_at' => ['nullable', 'date', 'after_or_equal:today'],
            'payment_terms' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $order = $service->create($data, (int) $request->user()->id);
        return response()->json(['message' => __('ui.workflow.purchase_order_draft_message'), 'id' => $order->id], 201);
    }

    public function show(PurchaseOrder $purchaseOrder, PurchaseOrderTableQuery $query): JsonResponse
    {
        $order = $query->details($purchaseOrder);
        return response()->json([
            'number' => $order->number,
            'purchase_request_number' => $order->purchaseRequest?->number ?? '—',
            'supplier' => $order->supplier?->name ?? 'Supplier dihapus',
            'creator' => $order->creator?->name ?? 'User dihapus',
            'order_date' => $order->order_date?->format('d/m/Y') ?? '—',
            'expected_delivery_at' => $order->expected_delivery_at?->format('d/m/Y') ?? '—',
            'issued_at' => $order->issued_at?->format('d/m/Y H:i') ?? '—',
            'status' => $order->status,
            'payment_terms' => $order->payment_terms ?: '—',
            'notes' => $order->notes ?: '—',
            'total_amount' => (float) $order->total_amount,
            'items' => $order->items->map(fn ($item) => [
                'code' => $item->product?->code ?? '—',
                'product' => $item->product?->name ?? 'Barang dihapus',
                'quantity' => number_format((float) $item->quantity, 4, ',', '.'),
                'received_quantity' => number_format((float) $item->received_quantity, 4, ',', '.'),
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ]),
        ]);
    }

    public function issue(PurchaseOrder $purchaseOrder, PurchaseOrderService $service): JsonResponse
    {
        $service->issue($purchaseOrder);
        return response()->json(['message' => __('ui.workflow.purchase_order_issued_message')]);
    }

    public function email(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $service): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:150']]);

        try {
            $service->sendEmail($purchaseOrder, $data['email'], (int) $request->user()->id);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => __('ui.workflow.purchase_order_email_failed_message'),
            ], 500);
        }

        return response()->json(['message' => __('ui.workflow.purchase_order_email_sent_message', ['number' => $purchaseOrder->number, 'email' => $data['email']])]);
    }

    public function emailHistory(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $logs = $purchaseOrder->emailLogs()->with('sender:id,name')->get();

        return response()->json([
            'number' => $purchaseOrder->number,
            'emails' => $logs->map(fn ($log) => [
                'recipient' => $log->recipient,
                'subject' => $log->subject,
                'sent_at' => $log->sent_at?->format('d/m/Y H:i:s'),
                'sender' => $log->sender?->name ?? 'User dihapus',
            ]),
        ]);
    }

    public function pdf(PurchaseOrder $purchaseOrder, PurchaseOrderTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $purchaseOrder = $query->details($purchaseOrder);
        $viewData = [...$branding->data(), 'purchaseOrder' => $purchaseOrder];
        return Pdf::loadView('super.purchase-orders.pdf', $viewData)
            ->setPaper('a4')
            ->stream($this->pdfFilename($purchaseOrder->number));
    }

    public function downloadPdf(PurchaseOrder $purchaseOrder, PurchaseOrderTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $purchaseOrder = $query->details($purchaseOrder);
        $viewData = [...$branding->data(), 'purchaseOrder' => $purchaseOrder];
        return Pdf::loadView('super.purchase-orders.pdf', $viewData)
            ->setPaper('a4')
            ->download($this->pdfFilename($purchaseOrder->number));
    }

    private function pdfFilename(string $number): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $number), '-_.') . '.pdf';
    }
}

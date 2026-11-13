<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Purchasing\Queries\PurchaseRequestTableQuery;
use App\Domain\Purchasing\Services\PurchaseRequestService;
use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Exports\PurchaseRequestsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        return view('super.purchase-requests.index');
    }

    public function create(PurchaseRequestTableQuery $query)
    {
        return view('super.purchase-requests.create', [
            'suppliers' => $query->activeSuppliers(),
        ]);
    }

    public function dt(Request $request, PurchaseRequestTableQuery $query): JsonResponse
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
            ->addColumn('material_request_number', fn (PurchaseRequest $request) => $request->materialRequest?->number ?? '—')
            ->addColumn('buyer_po_number', fn (PurchaseRequest $request) => $request->materialRequest?->productionOrder?->buyer_po_number ?: '—')
            ->addColumn('production_item', function (PurchaseRequest $request) {
                $materialRequest = $request->materialRequest;
                $order = $materialRequest?->productionOrder;
                $product = $order?->outputProduct ?? $materialRequest?->outputProduct;
                $quantity = $order?->planned_quantity ?? $materialRequest?->production_quantity;
                $styleCode = $product?->style_code;
                if (!$styleCode && $quantity === null) {
                    return '—';
                }

                $style = e($styleCode ?: '—');
                $qty = $quantity === null
                    ? '—'
                    : rtrim(rtrim(number_format((float) $quantity, 4, ',', '.'), '0'), ',');

                return '<span class="text-nowrap">' . $style . ' <span class="text-muted">/</span> ' . $qty . '</span>';
            })
            ->addColumn('required_items', function (PurchaseRequest $request) {
                if ($request->items->isEmpty()) return '—';
                $items = $request->items->map(function (PurchaseRequestItem $item) {
                    $code = e($item->product?->code ?? '—');
                    $name = e($item->product?->name ?? __('ui.workflow.deleted_item'));
                    $quantity = rtrim(rtrim(number_format((float) $item->quantity, 4, ',', '.'), '0'), ',');
                    $unit = $item->product?->unit?->symbol ?: $item->product?->unit?->name;
                    return '<span class="badge rounded-pill bg-light text-secondary border fw-normal" title="' . $name . '">' . $code . ' × ' . $quantity . ($unit ? ' ' . e($unit) : '') . '</span>';
                })->implode(' ');

                return '<div class="d-flex flex-wrap gap-1" style="max-width:24rem;max-height:4.25rem;overflow:auto">' . $items . '</div>';
            })
            ->addColumn('supplier_name', fn (PurchaseRequest $request) => $request->supplier?->name ?? __('ui.workflow.deleted_supplier'))
            ->addColumn('requester_name', fn (PurchaseRequest $request) => $request->requester?->name ?? __('ui.workflow.deleted_user'))
            ->addColumn('status_label', fn (PurchaseRequest $request) => match ($request->status) {
                'approved' => '<span class="badge bg-success">' . __('ui.workflow.approval_approved') . '</span>',
                'rejected' => '<span class="badge bg-danger">' . __('ui.workflow.approval_rejected') . '</span>',
                default => '<span class="badge bg-warning text-dark">' . __('ui.workflow.approval_pending') . '</span>',
            })
            ->addColumn('actions', function (PurchaseRequest $request) {
                $buttons = '<div class="d-inline-flex align-items-center justify-content-end gap-2 text-nowrap">'
                    . '<button type="button" class="btn btn-link btn-sm p-0 text-secondary btn-view-purchase-request" data-url="' . e(route('super.purchase-requests.show', $request)) . '" title="' . e(__('ui.workflow.view_detail')) . '" aria-label="' . e(__('ui.workflow.view_detail')) . '"><i data-feather="eye" class="icon-sm"></i></button>';
                $buttons .= '<a class="btn btn-link btn-sm p-0 text-primary" href="' . e(route('super.purchase-requests.pdf', $request)) . '" target="_blank" rel="noopener" title="' . e(__('ui.workflow.view_pdf')) . '" aria-label="' . e(__('ui.workflow.view_pdf')) . '"><i data-feather="file-text" class="icon-sm"></i></a>';
                $buttons .= '<a class="btn btn-link btn-sm p-0 text-secondary" href="' . e(route('super.purchase-requests.pdf.download', $request)) . '" title="' . e(__('ui.workflow.download_pdf')) . '" aria-label="' . e(__('ui.workflow.download_pdf')) . '"><i data-feather="download" class="icon-sm"></i></a>';
                if ($request->status === 'pending' && auth()->user()->can('purchase-requests.approve')) {
                    $buttons .= '<button type="button" class="btn btn-link btn-sm p-0 text-success btn-review-purchase-request" data-status="approved" data-url="' . e(route('super.purchase-requests.approve', $request)) . '" title="' . e(__('ui.workflow.approve')) . '" aria-label="' . e(__('ui.workflow.approve')) . '"><i data-feather="check" class="icon-sm"></i></button>';
                    $buttons .= '<button type="button" class="btn btn-link btn-sm p-0 text-danger btn-review-purchase-request" data-status="rejected" data-url="' . e(route('super.purchase-requests.reject', $request)) . '" title="' . e(__('ui.workflow.reject')) . '" aria-label="' . e(__('ui.workflow.reject')) . '"><i data-feather="x" class="icon-sm"></i></button>';
                }
                if ($request->status === 'approved' && !$request->purchaseOrder && auth()->user()->can('purchase-orders.create')) {
                    $buttons .= '<a class="btn btn-link btn-sm p-0 text-primary" href="' . e(route('super.purchase-orders.create', ['purchase_request' => $request->id])) . '" title="' . e(__('ui.workflow.create_purchase_order')) . '" aria-label="' . e(__('ui.workflow.create_purchase_order')) . '"><i data-feather="shopping-bag" class="icon-sm"></i></a>';
                }
                return $buttons . '</div>';
            })
            ->rawColumns(['status_label', 'production_item', 'required_items', 'actions'])
            ->toJson();
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
            new PurchaseRequestsExport($fromDate, $toDate),
            'purchase-requests-' . ($fromDate ?: 'all') . '-to-' . ($toDate ?: 'all') . '.xlsx'
        );
    }

    public function eligible(PurchaseRequestTableQuery $query)
    {
        return DataTables::eloquent(
            $query->eligibleMaterialRequests()->with('requester:id,name')->withCount('items')
        )
            ->addColumn('requester_name', fn ($materialRequest) => $materialRequest->requester?->name ?? __('ui.workflow.deleted_user'))
            ->addColumn('actions', fn ($materialRequest) => '<button type="button" class="btn btn-sm btn-primary btn-select-material-request" data-id="' . (int) $materialRequest->id . '" data-number="' . e($materialRequest->number) . '"><i data-feather="check" class="icon-sm me-1"></i>' . e(__('ui.workflow.select_action')) . '</button>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function source(int $materialRequestId, PurchaseRequestTableQuery $query): JsonResponse
    {
        $materialRequest = $query->sourceMaterialRequest($materialRequestId);
        $reservedQuantities = PurchaseRequestItem::query()
            ->whereIn('material_request_item_id', $materialRequest->items->pluck('id'))
            ->whereHas('purchaseRequest', fn ($builder) => $builder->whereIn('status', ['pending', 'approved']))
            ->selectRaw('material_request_item_id, SUM(quantity) as allocated_quantity')
            ->groupBy('material_request_item_id')
            ->pluck('allocated_quantity', 'material_request_item_id');

        return response()->json([
            'id' => $materialRequest->id,
            'number' => $materialRequest->number,
            'items' => $materialRequest->items->map(function ($item) use ($reservedQuantities) {
                $available = max(0, (float) $item->quantity - (float) ($reservedQuantities[$item->id] ?? 0));
                return [
                    'material_request_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'code' => $item->product?->code ?? '—',
                    'name' => $item->product?->name ?? 'Barang dihapus',
                    'quantity' => $item->quantity,
                    'available_quantity' => number_format($available, 4, '.', ''),
                    'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                    'unit_price' => $item->product?->purchase_price ?? 0,
                ];
            })->filter(fn ($item) => (float) $item['available_quantity'] > 0)->values(),
        ]);
    }

    public function store(Request $request, PurchaseRequestService $service): JsonResponse
    {
        $data = $request->validate([
            'material_request_id' => ['required', 'integer', 'exists:material_requests,id'],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_request_item_id' => ['required', 'distinct', 'integer', 'exists:material_request_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999999999.99'],
        ]);

        $purchaseRequest = $service->create($data, (int) $request->user()->id);
        return response()->json([
            'message' => __('ui.workflow.purchase_request_created_message'),
            'id' => $purchaseRequest->id,
        ], 201);
    }

    public function show(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $purchaseRequest->load(['materialRequest', 'supplier', 'requester', 'reviewer', 'items.product.unit']);
        return response()->json([
            'number' => $purchaseRequest->number,
            'material_request_number' => $purchaseRequest->materialRequest?->number ?? '—',
            'supplier' => $purchaseRequest->supplier?->name ?? 'Supplier dihapus',
            'requester' => $purchaseRequest->requester?->name ?? 'User dihapus',
            'total_amount' => (float) $purchaseRequest->total_amount,
            'notes' => $purchaseRequest->notes ?: '—',
            'status' => $purchaseRequest->status,
            'reviewer' => $purchaseRequest->reviewer?->name ?? '—',
            'reviewed_at' => $purchaseRequest->reviewed_at?->format('d/m/Y H:i') ?? '—',
            'review_note' => $purchaseRequest->review_note ?: '—',
            'items' => $purchaseRequest->items->map(fn ($item) => [
                'code' => $item->product?->code ?? '—',
                'product' => $item->product?->name ?? 'Barang dihapus',
                'quantity' => number_format((float) $item->quantity, 4, ',', '.'),
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ]),
        ]);
    }

    public function pdf(PurchaseRequest $purchaseRequest, CompanyPdfBrandingService $branding)
    {
        $purchaseRequest->load([
            'materialRequest.requester', 'supplier', 'requester', 'reviewer',
            'items.product.unit',
        ]);
        $filename = $this->pdfFilename($purchaseRequest->number);
        $viewData = [...$branding->data(), 'purchaseRequest' => $purchaseRequest];

        return Pdf::loadView('super.purchase-requests.pdf', $viewData)
            ->setPaper('a4')
            ->stream($filename);
    }

    public function downloadPdf(PurchaseRequest $purchaseRequest, CompanyPdfBrandingService $branding)
    {
        $purchaseRequest->load([
            'materialRequest.requester', 'supplier', 'requester', 'reviewer',
            'items.product.unit',
        ]);
        $filename = $this->pdfFilename($purchaseRequest->number);
        $viewData = [...$branding->data(), 'purchaseRequest' => $purchaseRequest];

        return Pdf::loadView('super.purchase-requests.pdf', $viewData)
            ->setPaper('a4')
            ->download($filename);
    }

    private function pdfFilename(string $number): string
    {
        $safeNumber = preg_replace('/[^A-Za-z0-9._-]+/', '-', $number);
        return trim($safeNumber, '-_.') . '.pdf';
    }

    public function approve(Request $request, PurchaseRequest $purchaseRequest, PurchaseRequestService $service): JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $service->review($purchaseRequest, (int) $request->user()->id, 'approved', $data['note'] ?? null);
        return response()->json(['message' => __('ui.workflow.purchase_request_approved_message')]);
    }

    public function reject(Request $request, PurchaseRequest $purchaseRequest, PurchaseRequestService $service): JsonResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $service->review($purchaseRequest, (int) $request->user()->id, 'rejected', $data['note']);
        return response()->json(['message' => __('ui.workflow.purchase_request_rejected_message')]);
    }
}

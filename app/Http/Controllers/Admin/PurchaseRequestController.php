<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Purchasing\Queries\PurchaseRequestTableQuery;
use App\Domain\Purchasing\Services\PurchaseRequestService;
use App\Domain\Companies\Services\CompanyPdfBrandingService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            ->addColumn('supplier_name', fn (PurchaseRequest $request) => $request->supplier?->name ?? 'Supplier dihapus')
            ->addColumn('requester_name', fn (PurchaseRequest $request) => $request->requester?->name ?? 'User dihapus')
            ->addColumn('status_label', fn (PurchaseRequest $request) => match ($request->status) {
                'approved' => '<span class="badge bg-success">Disetujui</span>',
                'rejected' => '<span class="badge bg-danger">Ditolak</span>',
                default => '<span class="badge bg-warning text-dark">Menunggu Approval</span>',
            })
            ->addColumn('actions', function (PurchaseRequest $request) {
                $buttons = '<button type="button" class="btn btn-sm btn-outline-secondary btn-view-purchase-request" data-url="' . e(route('super.purchase-requests.show', $request)) . '" title="Lihat detail"><i data-feather="eye"></i></button>';
                $buttons .= ' <a class="btn btn-sm btn-outline-primary" href="' . e(route('super.purchase-requests.pdf', $request)) . '" target="_blank" rel="noopener" title="Lihat PDF"><i data-feather="file-text"></i></a>';
                $buttons .= ' <a class="btn btn-sm btn-outline-secondary" href="' . e(route('super.purchase-requests.pdf.download', $request)) . '" title="Unduh PDF"><i data-feather="download"></i></a>';
                if ($request->status === 'pending' && auth()->user()->can('purchase-requests.approve')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-success btn-review-purchase-request" data-status="approved" data-url="' . e(route('super.purchase-requests.approve', $request)) . '" title="Setujui"><i data-feather="check"></i></button>';
                    $buttons .= ' <button type="button" class="btn btn-sm btn-outline-danger btn-review-purchase-request" data-status="rejected" data-url="' . e(route('super.purchase-requests.reject', $request)) . '" title="Tolak"><i data-feather="x"></i></button>';
                }
                if ($request->status === 'approved' && !$request->purchaseOrder && auth()->user()->can('purchase-orders.create')) {
                    $buttons .= ' <a class="btn btn-sm btn-outline-primary" href="' . e(route('super.purchase-orders.create', ['purchase_request' => $request->id])) . '" title="Buat Purchase Order"><i data-feather="shopping-bag"></i></a>';
                }
                return $buttons;
            })
            ->rawColumns(['status_label', 'actions'])
            ->toJson();
    }

    public function eligible(PurchaseRequestTableQuery $query)
    {
        return DataTables::eloquent(
            $query->eligibleMaterialRequests()->with('requester:id,name')->withCount('items')
        )
            ->addColumn('requester_name', fn ($materialRequest) => $materialRequest->requester?->name ?? 'User dihapus')
            ->addColumn('actions', fn ($materialRequest) => '<button type="button" class="btn btn-sm btn-primary btn-select-material-request" data-id="' . (int) $materialRequest->id . '" data-number="' . e($materialRequest->number) . '"><i data-feather="check" class="icon-sm me-1"></i>Pilih</button>')
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
            'message' => 'Purchase Request berhasil diajukan untuk approval.',
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
        return response()->json(['message' => 'Purchase Request berhasil disetujui.']);
    }

    public function reject(Request $request, PurchaseRequest $purchaseRequest, PurchaseRequestService $service): JsonResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $service->review($purchaseRequest, (int) $request->user()->id, 'rejected', $data['note']);
        return response()->json(['message' => 'Purchase Request ditolak.']);
    }
}

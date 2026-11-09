<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Purchasing\Queries\PurchaseOrderTableQuery;
use App\Domain\Purchasing\Services\PurchaseOrderService;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return view('super.purchase-orders.index');
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
            ->addColumn('supplier_name', fn (PurchaseOrder $order) => $order->supplier?->name ?? 'Supplier dihapus')
            ->addColumn('creator_name', fn (PurchaseOrder $order) => $order->creator?->name ?? 'User dihapus')
            ->addColumn('status_label', fn (PurchaseOrder $order) => match ($order->status) {
                'issued' => '<span class="badge bg-primary">Diterbitkan</span>',
                'partially_received' => '<span class="badge bg-info text-dark">Diterima Sebagian</span>',
                'received' => '<span class="badge bg-success">Selesai Diterima</span>',
                'cancelled' => '<span class="badge bg-danger">Dibatalkan</span>',
                default => '<span class="badge bg-secondary">Draft</span>',
            })
            ->addColumn('actions', function (PurchaseOrder $order) {
                $buttons = '<button type="button" class="btn btn-sm btn-outline-secondary btn-view-purchase-order" data-url="' . e(route('super.purchase-orders.show', $order)) . '" title="Lihat detail"><i data-feather="eye"></i></button>';
                $buttons .= ' <button type="button" class="btn btn-sm btn-outline-dark btn-email-history" data-url="' . e(route('super.purchase-orders.emails', $order)) . '" title="Riwayat email"><i data-feather="clock"></i></button>';
                $buttons .= ' <a class="btn btn-sm btn-outline-primary" href="' . e(route('super.purchase-orders.pdf', $order)) . '" target="_blank" rel="noopener" title="Lihat PDF"><i data-feather="file-text"></i></a>';
                $buttons .= ' <a class="btn btn-sm btn-outline-secondary" href="' . e(route('super.purchase-orders.pdf.download', $order)) . '" title="Unduh PDF"><i data-feather="download"></i></a>';
                if ($order->status === 'draft' && auth()->user()->can('purchase-orders.issue')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-success btn-issue-purchase-order" data-url="' . e(route('super.purchase-orders.issue', $order)) . '" title="Terbitkan PO"><i data-feather="send"></i></button>';
                }
                if (in_array($order->status, ['issued', 'partially_received', 'received'], true) && auth()->user()->can('purchase-orders.email')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-outline-primary btn-email-purchase-order" data-url="' . e(route('super.purchase-orders.email', $order)) . '" data-email="' . e($order->supplier?->email ?? '') . '" title="Kirim PO melalui email"><i data-feather="mail"></i></button>';
                }
                return $buttons;
            })
            ->rawColumns(['status_label', 'actions'])
            ->toJson();
    }

    public function eligible(PurchaseOrderTableQuery $query)
    {
        return DataTables::eloquent($query->eligiblePurchaseRequests())
            ->addColumn('supplier_name', fn (PurchaseRequest $request) => $request->supplier?->name ?? 'Supplier dihapus')
            ->addColumn('requester_name', fn (PurchaseRequest $request) => $request->requester?->name ?? 'User dihapus')
            ->addColumn('actions', fn (PurchaseRequest $request) => '<button type="button" class="btn btn-sm btn-primary btn-select-purchase-request" data-id="' . (int) $request->id . '" data-number="' . e($request->number) . '"><i data-feather="check" class="icon-sm me-1"></i>Pilih</button>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function source(int $purchaseRequestId, PurchaseOrderTableQuery $query): JsonResponse
    {
        $purchaseRequest = $query->sourcePurchaseRequest($purchaseRequestId);
        return response()->json([
            'id' => $purchaseRequest->id,
            'number' => $purchaseRequest->number,
            'supplier' => $purchaseRequest->supplier?->name ?? 'Supplier dihapus',
            'items' => $purchaseRequest->items->map(fn ($item) => [
                'code' => $item->product?->code ?? '—',
                'name' => $item->product?->name ?? 'Barang dihapus',
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
        return response()->json(['message' => 'Purchase Order berhasil dibuat sebagai draft.', 'id' => $order->id], 201);
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
        return response()->json(['message' => 'Purchase Order berhasil diterbitkan.']);
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
                'message' => 'Email gagal dikirim. Periksa Email Setting dan log aplikasi.',
            ], 500);
        }

        return response()->json(['message' => "Purchase Order {$purchaseOrder->number} berhasil dikirim ke {$data['email']}."]);
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

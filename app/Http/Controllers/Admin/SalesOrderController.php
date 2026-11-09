<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Sales\Queries\SalesOrderTableQuery;
use App\Domain\Sales\Services\SalesOrderService;
use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesQuotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SalesOrderController extends Controller
{
    public function index(): View { return view('super.sales-orders.index'); }

    public function create(): View { return view('super.sales-orders.create'); }

    public function dt(Request $request, SalesOrderTableQuery $query): JsonResponse
    {
        $filters = $this->dateFilters($request);
        return DataTables::eloquent($query->builder($filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('quotation_number', fn (SalesOrder $order) => $order->quotation?->number ?? '—')
            ->addColumn('customer_name', fn (SalesOrder $order) => $order->customer?->name ?? 'Customer dihapus')
            ->addColumn('status_label', fn (SalesOrder $order) => match ($order->status) {
                'confirmed' => '<span class="badge bg-primary">Dikonfirmasi</span>',
                'partially_delivered' => '<span class="badge bg-info text-dark">Dikirim Sebagian</span>',
                'delivered' => '<span class="badge bg-success">Selesai Dikirim</span>',
                'cancelled' => '<span class="badge bg-danger">Dibatalkan</span>',
                default => '<span class="badge bg-secondary">Draft</span>',
            })
            ->addColumn('actions', function (SalesOrder $order) {
                $buttons = '<button type="button" class="btn btn-sm btn-outline-secondary btn-view-sales-order" data-url="' . e(route('super.sales-orders.show', $order)) . '" title="Detail"><i data-feather="eye"></i></button>';
                $buttons .= ' <a class="btn btn-sm btn-outline-primary" href="' . e(route('super.sales-orders.pdf', $order)) . '" target="_blank" rel="noopener" title="Lihat PDF"><i data-feather="file-text"></i></a>';
                $buttons .= ' <a class="btn btn-sm btn-outline-secondary" href="' . e(route('super.sales-orders.pdf.download', $order)) . '" title="Unduh PDF"><i data-feather="download"></i></a>';
                if ($order->status === 'draft' && auth()->user()->can('sales-orders.confirm')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-success btn-confirm-sales-order" data-url="' . e(route('super.sales-orders.confirm', $order)) . '" data-number="' . e($order->number) . '" title="Konfirmasi Sales Order"><i data-feather="check"></i></button>';
                }
                return $buttons;
            })
            ->rawColumns(['status_label', 'actions'])->toJson();
    }

    public function eligible(SalesOrderTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->eligibleQuotations())
            ->addColumn('customer_name', fn (SalesQuotation $quotation) => $quotation->customer?->name ?? 'Customer dihapus')
            ->addColumn('actions', fn (SalesQuotation $quotation) => '<button type="button" class="btn btn-sm btn-primary btn-select-sales-quotation" data-id="' . (int) $quotation->id . '" data-number="' . e($quotation->number) . '"><i data-feather="check" class="icon-sm me-1"></i>Pilih</button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function source(int $quotationId, SalesOrderTableQuery $query): JsonResponse
    {
        $quotation = $query->sourceQuotation($quotationId);
        return response()->json([
            'id' => $quotation->id,
            'number' => $quotation->number,
            'customer' => $quotation->customer?->name ?? 'Customer dihapus',
            'valid_until' => $quotation->valid_until?->format('d/m/Y') ?? '—',
            'items' => $quotation->items->map(fn ($item) => [
                'code' => $item->product?->code ?? '—', 'name' => $item->product?->name ?? 'Barang dihapus',
                'quantity' => $item->quantity, 'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'unit_price' => $item->unit_price, 'discount_amount' => $item->discount_amount,
                'tax_rate' => $item->tax_rate, 'line_total' => $item->line_total,
            ]),
            'subtotal' => $quotation->subtotal, 'tax_amount' => $quotation->tax_amount, 'total_amount' => $quotation->total_amount,
        ]);
    }

    public function store(Request $request, SalesOrderService $service): JsonResponse
    {
        $data = $request->validate([
            'sales_quotation_id' => ['required', 'integer', 'exists:sales_quotations,id'],
            'order_date' => ['required', 'date_format:Y-m-d'],
            'requested_delivery_at' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('order_date') && $value < $request->input('order_date')) $fail('Tanggal pengiriman diminta harus sama atau setelah tanggal order.');
            }],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $order = $service->create($data, (int) $request->user()->id);
        return response()->json(['message' => "Sales Order {$order->number} berhasil dibuat sebagai draft.", 'url' => route('super.sales-orders.index')], 201);
    }

    public function show(SalesOrder $salesOrder, SalesOrderTableQuery $query): JsonResponse
    {
        $order = $query->details($salesOrder);
        return response()->json([
            'number' => $order->number, 'quotation' => $order->quotation?->number ?? '—',
            'customer' => $order->customer?->name ?? 'Customer dihapus', 'order_date' => $order->order_date?->format('d/m/Y'),
            'requested_delivery_at' => $order->requested_delivery_at?->format('d/m/Y') ?? '—', 'status' => $order->status,
            'creator' => $order->creator?->name ?? 'User dihapus', 'confirmer' => $order->confirmer?->name ?? '—',
            'confirmed_at' => $order->confirmed_at?->format('d/m/Y H:i') ?? '—', 'notes' => $order->notes ?: '—',
            'subtotal' => (float) $order->subtotal, 'tax_amount' => (float) $order->tax_amount, 'total_amount' => (float) $order->total_amount,
            'items' => $order->items->map(fn ($item) => [
                'code' => $item->product?->code ?? '—', 'name' => $item->product?->name ?? 'Barang dihapus',
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'quantity' => (float) $item->quantity, 'delivered_quantity' => (float) $item->delivered_quantity,
                'unit_price' => (float) $item->unit_price, 'line_total' => (float) $item->line_total,
            ]),
        ]);
    }

    public function confirm(Request $request, SalesOrder $salesOrder, SalesOrderService $service): JsonResponse
    {
        $order = $service->confirm($salesOrder, (int) $request->user()->id);
        return response()->json(['message' => "Sales Order {$order->number} berhasil dikonfirmasi. Stok akan berubah saat Delivery diposting."]);
    }

    public function pdf(SalesOrder $salesOrder, SalesOrderTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $order = $query->details($salesOrder);
        return Pdf::loadView('super.sales-orders.pdf', [...$branding->data(), 'order' => $order])->setPaper('a4')->stream($this->pdfFilename($order->number));
    }

    public function downloadPdf(SalesOrder $salesOrder, SalesOrderTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $order = $query->details($salesOrder);
        return Pdf::loadView('super.sales-orders.pdf', [...$branding->data(), 'order' => $order])->setPaper('a4')->download($this->pdfFilename($order->number));
    }

    private function dateFilters(Request $request): array
    {
        return $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) $fail('Tanggal akhir harus sama atau setelah tanggal awal.');
            }],
        ]);
    }

    private function pdfFilename(string $number): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $number), '-_.') . '.pdf';
    }
}

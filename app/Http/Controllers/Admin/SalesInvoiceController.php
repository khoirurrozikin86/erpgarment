<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Sales\Queries\SalesInvoiceTableQuery;
use App\Domain\Sales\Services\SalesInvoiceService;
use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\SalesInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SalesInvoiceController extends Controller
{
    public function index(): View { return view('super.sales-invoices.index'); }
    public function create(): View { return view('super.sales-invoices.create'); }

    public function dt(Request $request, SalesInvoiceTableQuery $query): JsonResponse
    {
        $filters = $this->dateFilters($request);
        return DataTables::eloquent($query->builder($filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('delivery_number', fn (SalesInvoice $invoice) => $invoice->delivery?->number ?? '—')
            ->addColumn('customer_name', fn (SalesInvoice $invoice) => $invoice->customer?->name ?? 'Customer dihapus')
            ->addColumn('status_label', fn (SalesInvoice $invoice) => $invoice->status === 'draft'
                ? '<span class="badge bg-secondary">Draft</span>'
                : ($invoice->due_date && $invoice->due_date->toDateString() < today()->toDateString()
                    ? '<span class="badge bg-warning text-dark">Jatuh Tempo</span>'
                    : '<span class="badge bg-success">Diterbitkan</span>'))
            ->addColumn('actions', function (SalesInvoice $invoice) {
                $buttons = '<button type="button" class="btn btn-sm btn-outline-secondary btn-view-sales-invoice" data-url="' . e(route('super.sales-invoices.show', $invoice)) . '" title="Detail"><i data-feather="eye"></i></button>';
                $buttons .= ' <a class="btn btn-sm btn-outline-primary" href="' . e(route('super.sales-invoices.pdf', $invoice)) . '" target="_blank" rel="noopener" title="Lihat PDF"><i data-feather="file-text"></i></a>';
                $buttons .= ' <a class="btn btn-sm btn-outline-secondary" href="' . e(route('super.sales-invoices.pdf.download', $invoice)) . '" title="Unduh PDF"><i data-feather="download"></i></a>';
                $buttons .= ' <button type="button" class="btn btn-sm btn-outline-dark btn-sales-invoice-history" data-url="' . e(route('super.sales-invoices.emails', $invoice)) . '" title="Riwayat Email"><i data-feather="clock"></i></button>';
                if ($invoice->status === 'draft' && auth()->user()->can('sales-invoices.issue')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-success btn-issue-sales-invoice" data-url="' . e(route('super.sales-invoices.issue', $invoice)) . '" data-number="' . e($invoice->number) . '" title="Terbitkan Invoice"><i data-feather="check"></i></button>';
                }
                if ($invoice->status === 'issued' && auth()->user()->can('sales-invoices.email')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-outline-primary btn-email-sales-invoice" data-url="' . e(route('super.sales-invoices.email', $invoice)) . '" data-email="' . e($invoice->customer?->email ?? '') . '" title="Kirim Invoice melalui Email"><i data-feather="mail"></i></button>';
                }
                return $buttons;
            })
            ->rawColumns(['status_label', 'actions'])->toJson();
    }

    public function eligible(SalesInvoiceTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->eligibleDeliveries())
            ->addColumn('sales_order_number', fn (Delivery $delivery) => $delivery->salesOrder?->number ?? '—')
            ->addColumn('customer_name', fn (Delivery $delivery) => $delivery->customer?->name ?? 'Customer dihapus')
            ->addColumn('actions', fn (Delivery $delivery) => '<button type="button" class="btn btn-sm btn-primary btn-select-invoice-delivery" data-id="' . (int) $delivery->id . '" data-number="' . e($delivery->number) . '"><i data-feather="check" class="icon-sm me-1"></i>Pilih</button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function source(int $deliveryId, SalesInvoiceTableQuery $query): JsonResponse
    {
        $delivery = $query->sourceDelivery($deliveryId);
        $subtotal = 0; $taxAmount = 0;
        $items = $delivery->items->map(function ($deliveryItem) use (&$subtotal, &$taxAmount) {
            $orderItem = $deliveryItem->salesOrderItem;
            $quantity = (float) $deliveryItem->quantity;
            $gross = round($quantity * (float) ($orderItem?->unit_price ?? 0), 2);
            $discount = (float) ($orderItem?->quantity ?? 0) > 0 ? round((float) $orderItem->discount_amount * $quantity / (float) $orderItem->quantity, 2) : 0;
            $net = max(0, round($gross - $discount, 2));
            $rate = (float) ($orderItem?->tax_rate ?? 0);
            $tax = round($net * $rate / 100, 2);
            $total = round($net + $tax, 2);
            $subtotal += $net; $taxAmount += $tax;
            return [
                'code' => $deliveryItem->product?->code ?? '—', 'name' => $deliveryItem->product?->name ?? 'Barang dihapus',
                'unit' => $deliveryItem->product?->unit?->symbol ?: $deliveryItem->product?->unit?->name ?: '—',
                'quantity' => $quantity, 'unit_price' => (float) ($orderItem?->unit_price ?? 0),
                'discount_amount' => $discount, 'tax_rate' => $rate, 'line_total' => $total,
            ];
        });
        return response()->json([
            'id' => $delivery->id, 'number' => $delivery->number,
            'sales_order' => $delivery->salesOrder?->number ?? '—', 'customer' => $delivery->customer?->name ?? 'Customer dihapus',
            'email' => $delivery->customer?->email ?? '', 'items' => $items,
            'subtotal' => round($subtotal, 2), 'tax_amount' => round($taxAmount, 2), 'total_amount' => round($subtotal + $taxAmount, 2),
        ]);
    }

    public function store(Request $request, SalesInvoiceService $service): JsonResponse
    {
        $data = $request->validate([
            'delivery_id' => ['required', 'integer', 'exists:deliveries,id'],
            'invoice_date' => ['required', 'date_format:Y-m-d'],
            'due_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('invoice_date') && $value < $request->input('invoice_date')) $fail('Tanggal jatuh tempo harus sama atau setelah tanggal invoice.');
            }],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $invoice = $service->create($data, (int) $request->user()->id);
        return response()->json(['message' => "Sales Invoice {$invoice->number} berhasil dibuat sebagai draft.", 'url' => route('super.sales-invoices.index')], 201);
    }

    public function show(SalesInvoice $salesInvoice, SalesInvoiceTableQuery $query): JsonResponse
    {
        $invoice = $query->details($salesInvoice);
        return response()->json([
            'number' => $invoice->number, 'delivery' => $invoice->delivery?->number ?? '—', 'sales_order' => $invoice->salesOrder?->number ?? '—',
            'customer' => $invoice->customer?->name ?? 'Customer dihapus', 'email' => $invoice->customer?->email ?? '—',
            'invoice_date' => $invoice->invoice_date?->format('d/m/Y'), 'due_date' => $invoice->due_date?->format('d/m/Y') ?? '—',
            'status' => $invoice->status, 'creator' => $invoice->creator?->name ?? 'User dihapus', 'issuer' => $invoice->issuer?->name ?? '—',
            'issued_at' => $invoice->issued_at?->format('d/m/Y H:i') ?? '—', 'notes' => $invoice->notes ?: '—',
            'subtotal' => (float) $invoice->subtotal, 'tax_amount' => (float) $invoice->tax_amount, 'total_amount' => (float) $invoice->total_amount,
            'items' => $invoice->items->map(fn ($item) => [
                'code' => $item->product?->code ?? '—', 'name' => $item->product?->name ?? 'Barang dihapus',
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—', 'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price, 'discount_amount' => (float) $item->discount_amount,
                'tax_rate' => (float) $item->tax_rate, 'line_total' => (float) $item->line_total,
            ]),
        ]);
    }

    public function issue(Request $request, SalesInvoice $salesInvoice, SalesInvoiceService $service): JsonResponse
    {
        $invoice = $service->issue($salesInvoice, (int) $request->user()->id);
        return response()->json(['message' => "Sales Invoice {$invoice->number} berhasil diterbitkan."]);
    }

    public function email(Request $request, SalesInvoice $salesInvoice, SalesInvoiceService $service): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:150']]);
        try {
            $service->sendEmail($salesInvoice, $data['email'], (int) $request->user()->id);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'Email gagal dikirim. Periksa Email Setting dan log aplikasi.'], 500);
        }
        return response()->json(['message' => "Sales Invoice {$salesInvoice->number} berhasil dikirim ke {$data['email']}."]);
    }

    public function emailHistory(SalesInvoice $salesInvoice): JsonResponse
    {
        return response()->json([
            'number' => $salesInvoice->number,
            'emails' => $salesInvoice->emailLogs()->with('sender:id,name')->get()->map(fn ($log) => [
                'recipient' => $log->recipient, 'subject' => $log->subject,
                'sent_at' => $log->sent_at?->format('d/m/Y H:i:s'), 'sender' => $log->sender?->name ?? 'User dihapus',
            ]),
        ]);
    }

    public function pdf(SalesInvoice $salesInvoice, SalesInvoiceTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $invoice = $query->details($salesInvoice);
        return Pdf::loadView('super.sales-invoices.pdf', [...$branding->data(), 'invoice' => $invoice])->setPaper('a4')->stream($this->pdfFilename($invoice->number));
    }

    public function downloadPdf(SalesInvoice $salesInvoice, SalesInvoiceTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $invoice = $query->details($salesInvoice);
        return Pdf::loadView('super.sales-invoices.pdf', [...$branding->data(), 'invoice' => $invoice])->setPaper('a4')->download($this->pdfFilename($invoice->number));
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

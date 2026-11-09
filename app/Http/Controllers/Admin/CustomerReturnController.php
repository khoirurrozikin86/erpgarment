<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Sales\Queries\CustomerReturnTableQuery;
use App\Domain\Sales\Services\CustomerReturnService;
use App\Http\Controllers\Controller;
use App\Models\CashBankAccount;
use App\Models\CustomerReturn;
use App\Models\SalesInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CustomerReturnController extends Controller
{
    public function index()
    {
        return view('super.customer-returns.index');
    }

    public function create(CompanyContext $company)
    {
        $accounts = CashBankAccount::query()
            ->where('company_id', $company->id())
            ->where('is_active', true)
            ->whereHas('chartOfAccount', fn($query) => $query
                ->where('company_id', $company->id())
                ->where('is_active', true)
                ->where('is_group', false)
                ->where('account_type', 'asset'))
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'type']);

        return view('super.customer-returns.create', compact('accounts'));
    }

    public function dt(Request $request, CustomerReturnTableQuery $query): JsonResponse
    {
        $filters = $this->dateFilters($request);

        return DataTables::eloquent($query->builder($filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('invoice_number', fn(CustomerReturn $customerReturn) => $customerReturn->invoice?->number ?? '—')
            ->addColumn('customer_name', fn(CustomerReturn $customerReturn) => $customerReturn->customer?->name ?? 'Customer dihapus')
            ->addColumn('cash_account', fn(CustomerReturn $customerReturn) => $customerReturn->cashBankAccount?->name ?? 'Akun dihapus')
            ->addColumn('returner_name', fn(CustomerReturn $customerReturn) => $customerReturn->returner?->name ?? 'User dihapus')
            ->addColumn('actions', fn(CustomerReturn $customerReturn) => '<button type="button" class="btn btn-sm btn-outline-secondary btn-view-customer-return" data-url="' . e(route('super.customer-returns.show', $customerReturn)) . '" title="Lihat rincian"><i data-feather="eye"></i></button>'
                . ' <a class="btn btn-sm btn-outline-primary" href="' . e(route('super.customer-returns.pdf', $customerReturn)) . '" target="_blank" rel="noopener" title="Lihat PDF"><i data-feather="file-text" class="icon-sm"></i></a>'
                . ' <a class="btn btn-sm btn-outline-secondary" href="' . e(route('super.customer-returns.pdf.download', $customerReturn)) . '" title="Unduh PDF"><i data-feather="download"></i></a>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function eligible(CustomerReturnTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->eligibleInvoices())
            ->addColumn('customer_name', fn(SalesInvoice $invoice) => $invoice->customer?->name ?? 'Customer dihapus')
            ->addColumn('actions', fn(SalesInvoice $invoice) => '<button type="button" class="btn btn-sm btn-primary btn-select-return-invoice" data-id="' . (int) $invoice->id . '"><i data-feather="check" class="icon-sm me-1"></i>Pilih</button>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function source(int $salesInvoiceId, CustomerReturnTableQuery $query): JsonResponse
    {
        $invoice = $query->sourceInvoice($salesInvoiceId);

        return response()->json([
            'id' => $invoice->id,
            'number' => $invoice->number,
            'customer' => $invoice->customer?->name ?? 'Customer dihapus',
            'date' => $invoice->invoice_date?->format('d/m/Y'),
            'items' => $invoice->items
                ->filter(fn($item) => (float) $item->quantity > (float) $item->returned_quantity)
                ->map(fn($item) => [
                    'id' => $item->id,
                    'code' => $item->product?->code ?? '—',
                    'name' => $item->product?->name ?? 'Barang dihapus',
                    'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                    'sold' => (float) $item->quantity,
                    'returned' => (float) $item->returned_quantity,
                    'available' => max(0, round((float) $item->quantity - (float) $item->returned_quantity, 4)),
                    'unit_price' => (float) $item->unit_price,
                    'discount_amount' => (float) $item->discount_amount,
                    'tax_rate' => (float) $item->tax_rate,
                    'track_stock' => (bool) ($item->product?->track_stock ?? false),
                ])->values(),
        ]);
    }

    public function store(Request $request, CustomerReturnService $service): JsonResponse
    {
        $data = $request->validate([
            'sales_invoice_id' => ['required', 'integer', 'exists:sales_invoices,id'],
            'cash_bank_account_id' => ['required', 'integer', 'exists:cash_bank_accounts,id'],
            'returned_at' => ['required', 'date', 'before_or_equal:now'],
            'reason' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_invoice_item_id' => ['required', 'integer', 'distinct', 'exists:sales_invoice_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0', 'max:999999999999.9999'],
        ]);

        $customerReturn = $service->create($data, (int) $request->user()->id);

        return response()->json([
            'message' => "Customer Return {$customerReturn->number} berhasil dicatat. Refund dibukukan dan stok barang retur diperbarui.",
            'id' => $customerReturn->id,
        ], 201);
    }

    public function show(CustomerReturn $customerReturn, CustomerReturnTableQuery $query): JsonResponse
    {
        $customerReturn = $query->details($customerReturn);

        return response()->json([
            'number' => $customerReturn->number,
            'invoice' => $customerReturn->invoice?->number ?? '—',
            'customer' => $customerReturn->customer?->name ?? 'Customer dihapus',
            'cash_account' => $customerReturn->cashBankAccount?->name ?? 'Akun dihapus',
            'returner' => $customerReturn->returner?->name ?? 'User dihapus',
            'returned_at' => $customerReturn->returned_at?->format('d/m/Y H:i') ?? '—',
            'reason' => $customerReturn->reason,
            'notes' => $customerReturn->notes ?: '—',
            'subtotal' => (float) $customerReturn->subtotal,
            'tax_amount' => (float) $customerReturn->tax_amount,
            'total_amount' => (float) $customerReturn->total_amount,
            'items' => $customerReturn->items->map(fn($item) => [
                'code' => $item->product?->code ?? '—',
                'name' => $item->product?->name ?? 'Barang dihapus',
                'quantity' => number_format((float) $item->quantity, 4, ',', '.'),
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ]),
        ]);
    }

    public function pdf(CustomerReturn $customerReturn, CustomerReturnTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $customerReturn = $query->details($customerReturn);

        return Pdf::loadView('super.customer-returns.pdf', [
            ...$branding->data(),
            'customerReturn' => $customerReturn,
        ])->setPaper('a4')->stream($this->pdfFilename($customerReturn->number));
    }

    public function downloadPdf(CustomerReturn $customerReturn, CustomerReturnTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $customerReturn = $query->details($customerReturn);

        return Pdf::loadView('super.customer-returns.pdf', [
            ...$branding->data(),
            'customerReturn' => $customerReturn,
        ])->setPaper('a4')->download($this->pdfFilename($customerReturn->number));
    }

    private function dateFilters(Request $request): array
    {
        return $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) {
                    $fail('Tanggal akhir harus sama atau setelah tanggal awal.');
                }
            }],
        ]);
    }

    private function pdfFilename(string $number): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $number), '-_.') . '.pdf';
    }
}

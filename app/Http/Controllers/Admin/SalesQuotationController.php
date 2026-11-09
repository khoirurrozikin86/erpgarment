<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Sales\Queries\SalesQuotationTableQuery;
use App\Domain\Sales\Services\SalesQuotationService;
use App\Http\Controllers\Controller;
use App\Models\SalesQuotation;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SalesQuotationController extends Controller
{
    public function index(): View
    {
        return view('super.sales-quotations.index');
    }

    public function create(SalesQuotationTableQuery $query): View
    {
        return view('super.sales-quotations.create', ['customers' => $query->customers()]);
    }

    public function dt(Request $request, SalesQuotationTableQuery $query): JsonResponse
    {
        $filters = $this->dateFilters($request);
        return DataTables::eloquent($query->builder($filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('customer_name', fn (SalesQuotation $quotation) => $quotation->customer?->name ?? 'Customer dihapus')
            ->addColumn('creator_name', fn (SalesQuotation $quotation) => $quotation->creator?->name ?? 'User dihapus')
            ->addColumn('status_label', fn (SalesQuotation $quotation) => $this->statusBadge($quotation))
            ->addColumn('actions', function (SalesQuotation $quotation) {
                $buttons = '<button type="button" class="btn btn-sm btn-outline-secondary btn-view-quotation" data-url="' . e(route('super.sales-quotations.show', $quotation)) . '" title="Lihat detail"><i data-feather="eye"></i></button>';
                $buttons .= ' <a class="btn btn-sm btn-outline-primary" href="' . e(route('super.sales-quotations.pdf', $quotation)) . '" target="_blank" rel="noopener" title="Lihat PDF"><i data-feather="file-text"></i></a>';
                $buttons .= ' <a class="btn btn-sm btn-outline-secondary" href="' . e(route('super.sales-quotations.pdf.download', $quotation)) . '" title="Unduh PDF"><i data-feather="download"></i></a>';
                $buttons .= ' <button type="button" class="btn btn-sm btn-outline-dark btn-quotation-email-history" data-url="' . e(route('super.sales-quotations.emails', $quotation)) . '" title="Riwayat email"><i data-feather="clock"></i></button>';
                if ($quotation->status === 'sent' && auth()->user()->can('sales-quotations.email')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-outline-primary btn-email-quotation" data-url="' . e(route('super.sales-quotations.email', $quotation)) . '" data-email="' . e($quotation->customer?->email ?? '') . '" title="Kirim quotation melalui email"><i data-feather="mail"></i></button>';
                }
                if ($quotation->status === 'draft' && auth()->user()->can('sales-quotations.issue')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-success btn-issue-quotation" data-url="' . e(route('super.sales-quotations.issue', $quotation)) . '" data-number="' . e($quotation->number) . '" title="Terbitkan quotation"><i data-feather="send"></i></button>';
                }
                if ($quotation->status === 'sent' && auth()->user()->can('sales-quotations.review')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-outline-success btn-review-quotation" data-url="' . e(route('super.sales-quotations.review', $quotation)) . '" data-status="accepted" data-number="' . e($quotation->number) . '" title="Tandai disetujui customer"><i data-feather="check"></i></button>';
                    $buttons .= ' <button type="button" class="btn btn-sm btn-outline-danger btn-review-quotation" data-url="' . e(route('super.sales-quotations.review', $quotation)) . '" data-status="rejected" data-number="' . e($quotation->number) . '" title="Tandai ditolak customer"><i data-feather="x"></i></button>';
                }
                return $buttons;
            })
            ->rawColumns(['status_label', 'actions'])
            ->toJson();
    }

    public function productsDt(Request $request, SalesQuotationTableQuery $query): JsonResponse
    {
        $data = $request->validate(['customer_id' => ['nullable', 'integer', 'exists:customers,id']]);
        return DataTables::eloquent($query->products(isset($data['customer_id']) ? (int) $data['customer_id'] : null))
            ->addColumn('unit_label', fn (Product $product) => $product->unit?->symbol ?: $product->unit?->name ?: '—')
            ->addColumn('quote_price', fn (Product $product) => $product->price_list_price ?? $product->sales_price ?? 0)
            ->addColumn('actions', fn (Product $product) => '<button type="button" class="btn btn-sm btn-primary btn-add-quotation-product" data-id="' . (int) $product->id . '" data-code="' . e($product->code) . '" data-name="' . e($product->name) . '" data-unit="' . e($product->unit?->symbol ?: $product->unit?->name ?: '') . '" data-price="' . e($product->price_list_price ?? $product->sales_price ?? 0) . '" data-taxable="' . ($product->taxable ? '1' : '0') . '" data-tax-rate="' . e($product->tax_rate ?? 0) . '"><i data-feather="plus" class="icon-sm me-1"></i>Pilih</button>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function store(Request $request, SalesQuotationService $service): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'quote_date' => ['required', 'date_format:Y-m-d'],
            'valid_until' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('quote_date') && $value < $request->input('quote_date')) $fail('Tanggal berlaku harus sama atau setelah tanggal quotation.');
            }],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
        ]);
        $quotation = $service->create($data, (int) $request->user()->id);
        return response()->json(['message' => "Quotation {$quotation->number} berhasil dibuat sebagai draft.", 'url' => route('super.sales-quotations.index')], 201);
    }

    public function show(SalesQuotation $salesQuotation, SalesQuotationTableQuery $query): JsonResponse
    {
        $quotation = $query->details($salesQuotation);
        return response()->json([
            'number' => $quotation->number,
            'customer' => $quotation->customer?->name ?? 'Customer dihapus',
            'customer_email' => $quotation->customer?->email ?? '—',
            'quote_date' => $quotation->quote_date?->format('d/m/Y'),
            'valid_until' => $quotation->valid_until?->format('d/m/Y') ?? '—',
            'status' => $quotation->status,
            'creator' => $quotation->creator?->name ?? 'User dihapus',
            'issuer' => $quotation->issuer?->name ?? '—',
            'reviewer' => $quotation->reviewer?->name ?? '—',
            'review_note' => $quotation->review_note ?: '—',
            'notes' => $quotation->notes ?: '—',
            'subtotal' => (float) $quotation->subtotal,
            'tax_amount' => (float) $quotation->tax_amount,
            'total_amount' => (float) $quotation->total_amount,
            'items' => $quotation->items->map(fn ($item) => [
                'code' => $item->product?->code ?? '—', 'name' => $item->product?->name ?? 'Barang dihapus',
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'quantity' => (float) $item->quantity, 'unit_price' => (float) $item->unit_price,
                'discount_amount' => (float) $item->discount_amount, 'tax_rate' => (float) $item->tax_rate,
                'line_total' => (float) $item->line_total,
            ]),
        ]);
    }

    public function issue(Request $request, SalesQuotation $salesQuotation, SalesQuotationService $service): JsonResponse
    {
        $quotation = $service->transition($salesQuotation, 'sent', null, (int) $request->user()->id);
        return response()->json(['message' => "Quotation {$quotation->number} berhasil diterbitkan."]);
    }

    public function review(Request $request, SalesQuotation $salesQuotation, SalesQuotationService $service): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:accepted,rejected'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $quotation = $service->transition($salesQuotation, $data['status'], $data['note'] ?? null, (int) $request->user()->id);
        return response()->json(['message' => "Quotation {$quotation->number} ditandai " . ($quotation->status === 'accepted' ? 'disetujui' : 'ditolak') . ' oleh customer.']);
    }

    public function email(Request $request, SalesQuotation $salesQuotation, SalesQuotationService $service): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:150']]);
        try {
            $service->sendEmail($salesQuotation, $data['email'], (int) $request->user()->id);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'Email gagal dikirim. Periksa Email Setting dan log aplikasi.'], 500);
        }
        return response()->json(['message' => "Quotation {$salesQuotation->number} berhasil dikirim ke {$data['email']}."]);
    }

    public function emailHistory(SalesQuotation $salesQuotation): JsonResponse
    {
        $logs = $salesQuotation->emailLogs()->with('sender:id,name')->get();
        return response()->json([
            'number' => $salesQuotation->number,
            'emails' => $logs->map(fn ($log) => [
                'recipient' => $log->recipient,
                'subject' => $log->subject,
                'sent_at' => $log->sent_at?->format('d/m/Y H:i:s'),
                'sender' => $log->sender?->name ?? 'User dihapus',
            ]),
        ]);
    }

    public function pdf(SalesQuotation $salesQuotation, SalesQuotationTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $quotation = $query->details($salesQuotation);
        return Pdf::loadView('super.sales-quotations.pdf', [...$branding->data(), 'quotation' => $quotation])->setPaper('a4')->stream($this->pdfFilename($quotation->number));
    }

    public function downloadPdf(SalesQuotation $salesQuotation, SalesQuotationTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $quotation = $query->details($salesQuotation);
        return Pdf::loadView('super.sales-quotations.pdf', [...$branding->data(), 'quotation' => $quotation])->setPaper('a4')->download($this->pdfFilename($quotation->number));
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

    private function statusBadge(SalesQuotation $quotation): string
    {
        if ($quotation->status === 'sent' && $quotation->valid_until && $quotation->valid_until->toDateString() < today()->toDateString()) return '<span class="badge bg-warning text-dark">Kedaluwarsa</span>';
        return match ($quotation->status) {
            'sent' => '<span class="badge bg-primary">Diterbitkan</span>',
            'accepted' => '<span class="badge bg-success">Disetujui</span>',
            'rejected' => '<span class="badge bg-danger">Ditolak</span>',
            default => '<span class="badge bg-secondary">Draft</span>',
        };
    }

    private function pdfFilename(string $number): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $number), '-_.') . '.pdf';
    }
}

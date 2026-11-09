<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Purchasing\Services\MaterialRequestService;
use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Purchasing\Queries\MaterialRequestTableQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class MaterialRequestController extends Controller
{
    public function index()
    {
        $products = Product::query()->where('is_active', true)->orderBy('name')
            ->get(['id', 'code', 'name', 'unit_id'])->load('unit:id,name,symbol')
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'label' => $product->code . ' — ' . $product->name,
                'unit' => $product->unit?->symbol ?: $product->unit?->name,
            ]);

        return view('super.material-requests.index', compact('products'));
    }

    public function dt(Request $request, MaterialRequestTableQuery $query)
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
            ->addColumn('requester_name', fn (MaterialRequest $request) => $request->requester?->name ?? 'User dihapus')
            ->addColumn('items_count', fn (MaterialRequest $request) => $request->items_count)
            ->addColumn('status_label', fn (MaterialRequest $request) => match ($request->status) {
                'approved' => '<span class="badge bg-success">Disetujui</span>',
                'rejected' => '<span class="badge bg-danger">Ditolak</span>',
                default => '<span class="badge bg-warning text-dark">Menunggu Approval</span>',
            })
            ->addColumn('purchase_status_label', fn (MaterialRequest $request) => match ($request->purchase_status) {
                'processed' => '<span class="badge bg-success">Sudah Dialokasikan</span>',
                'partially_processed' => '<span class="badge bg-info text-dark">Sebagian Dialokasikan</span>',
                default => '<span class="badge bg-secondary">Belum Diproses Purchasing</span>',
            })
            ->addColumn('actions', function (MaterialRequest $request) {
                $buttons = '<button type="button" class="btn btn-sm btn-outline-secondary btn-view-request" data-url="' . e(route('super.material-requests.show', $request)) . '" title="Lihat detail"><i data-feather="eye"></i></button>';
                $buttons .= ' <a class="btn btn-sm btn-outline-primary" href="' . e(route('super.material-requests.pdf', $request)) . '" target="_blank" rel="noopener" title="Lihat PDF"><i data-feather="file-text"></i></a>';
                $buttons .= ' <a class="btn btn-sm btn-outline-secondary" href="' . e(route('super.material-requests.pdf.download', $request)) . '" title="Unduh PDF"><i data-feather="download"></i></a>';
                if ($request->status === 'pending' && auth()->user()->can('material-requests.approve')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-success btn-review-request" data-status="approved" data-url="' . e(route('super.material-requests.approve', $request)) . '" title="Setujui"><i data-feather="check"></i></button>';
                    $buttons .= ' <button type="button" class="btn btn-sm btn-outline-danger btn-review-request" data-status="rejected" data-url="' . e(route('super.material-requests.reject', $request)) . '" title="Tolak"><i data-feather="x"></i></button>';
                }
                return $buttons;
            })
            ->rawColumns(['status_label', 'purchase_status_label', 'actions'])
            ->toJson();
    }

    public function store(Request $request, MaterialRequestService $service): JsonResponse
    {
        $data = $request->validate([
            'department' => ['nullable', 'string', 'max:120'],
            'needed_at' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $materialRequest = $service->create($data, (int) $request->user()->id);

        return response()->json(['message' => 'Material Request berhasil diajukan untuk approval.', 'id' => $materialRequest->id], 201);
    }

    public function show(MaterialRequest $materialRequest, MaterialRequestTableQuery $query): JsonResponse
    {
        $materialRequest = $query->details($materialRequest);
        return response()->json([
            'number' => $materialRequest->number,
            'requester' => $materialRequest->requester?->name ?? 'User dihapus',
            'department' => $materialRequest->department ?: '—',
            'needed_at' => $materialRequest->needed_at?->format('d/m/Y') ?? '—',
            'reason' => $materialRequest->reason,
            'status' => $materialRequest->status,
            'purchase_status' => $materialRequest->purchase_status,
            'reviewer' => $materialRequest->reviewer?->name ?? '—',
            'reviewed_at' => $materialRequest->reviewed_at?->format('d/m/Y H:i') ?? '—',
            'review_note' => $materialRequest->review_note ?: '—',
            'items' => $materialRequest->items->map(fn ($item) => [
                'product' => $item->product?->name ?? 'Barang dihapus',
                'code' => $item->product?->code ?? '—',
                'quantity' => number_format((float) $item->quantity, 4, ',', '.'),
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'note' => $item->note ?: '—',
            ]),
        ]);
    }

    public function pdf(MaterialRequest $materialRequest, MaterialRequestTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $materialRequest = $query->details($materialRequest);
        $filename = $this->pdfFilename($materialRequest->number);
        $viewData = [...$branding->data(), 'materialRequest' => $materialRequest];

        return Pdf::loadView('super.material-requests.pdf', $viewData)
            ->setPaper('a4')
            ->stream($filename);
    }

    public function downloadPdf(MaterialRequest $materialRequest, MaterialRequestTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $materialRequest = $query->details($materialRequest);
        $filename = $this->pdfFilename($materialRequest->number);
        $viewData = [...$branding->data(), 'materialRequest' => $materialRequest];

        return Pdf::loadView('super.material-requests.pdf', $viewData)
            ->setPaper('a4')
            ->download($filename);
    }

    private function pdfFilename(string $number): string
    {
        $safeNumber = preg_replace('/[^A-Za-z0-9._-]+/', '-', $number);
        return trim($safeNumber, '-_.') . '.pdf';
    }

    public function approve(Request $request, MaterialRequest $materialRequest, MaterialRequestService $service): JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $service->review($materialRequest, (int) $request->user()->id, 'approved', $data['note'] ?? null);
        return response()->json(['message' => 'Material Request berhasil disetujui.']);
    }

    public function reject(Request $request, MaterialRequest $materialRequest, MaterialRequestService $service): JsonResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $service->review($materialRequest, (int) $request->user()->id, 'rejected', $data['note']);
        return response()->json(['message' => 'Material Request ditolak.']);
    }
}

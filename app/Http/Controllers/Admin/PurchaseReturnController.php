<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Purchasing\Queries\PurchaseReturnTableQuery;
use App\Domain\Purchasing\Services\PurchaseReturnService;
use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\PurchaseReturn;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PurchaseReturnController extends Controller
{
    public function index()
    {
        return view('super.purchase-returns.index');
    }

    public function create()
    {
        return view('super.purchase-returns.create');
    }

    public function dt(Request $request, PurchaseReturnTableQuery $query): JsonResponse
    {
        $filters = $this->dateFilters($request);

        return DataTables::eloquent($query->builder($filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('receipt_number', fn (PurchaseReturn $purchaseReturn) => $purchaseReturn->goodsReceipt?->number ?? '—')
            ->addColumn('supplier_name', fn (PurchaseReturn $purchaseReturn) => $purchaseReturn->supplier?->name ?? 'Supplier dihapus')
            ->addColumn('returner_name', fn (PurchaseReturn $purchaseReturn) => $purchaseReturn->returner?->name ?? 'User dihapus')
            ->addColumn('actions', function (PurchaseReturn $purchaseReturn) {
                return '<button type="button" class="btn btn-sm btn-outline-secondary btn-view-purchase-return" data-url="' . e(route('super.purchase-returns.show', $purchaseReturn)) . '" title="Lihat rincian"><i data-feather="eye"></i></button>'
                    . ' <a class="btn btn-sm btn-outline-primary" href="' . e(route('super.purchase-returns.pdf', $purchaseReturn)) . '" target="_blank" rel="noopener" title="Lihat PDF"><i data-feather="file-text"></i></a>'
                    . ' <a class="btn btn-sm btn-outline-secondary" href="' . e(route('super.purchase-returns.pdf.download', $purchaseReturn)) . '" title="Unduh PDF"><i data-feather="download"></i></a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function eligible(Request $request, PurchaseReturnTableQuery $query)
    {
        return DataTables::eloquent($query->eligibleGoodsReceipts())
            ->addColumn('purchase_order_number', fn (GoodsReceipt $receipt) => $receipt->purchaseOrder?->number ?? '—')
            ->addColumn('supplier_name', fn (GoodsReceipt $receipt) => $receipt->supplier?->name ?? 'Supplier dihapus')
            ->addColumn('actions', fn (GoodsReceipt $receipt) => '<button type="button" class="btn btn-sm btn-primary btn-select-return-receipt" data-id="' . (int) $receipt->id . '"><i data-feather="check" class="icon-sm me-1"></i>Pilih</button>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function source(int $goodsReceiptId, PurchaseReturnTableQuery $query): JsonResponse
    {
        $receipt = $query->sourceGoodsReceipt($goodsReceiptId);

        return response()->json([
            'id' => $receipt->id,
            'number' => $receipt->number,
            'purchase_order' => $receipt->purchaseOrder?->number ?? '—',
            'supplier' => $receipt->supplier?->name ?? 'Supplier dihapus',
            'items' => $receipt->items->map(fn ($item) => [
                'id' => $item->id,
                'code' => $item->product?->code ?? '—',
                'name' => $item->product?->name ?? 'Barang dihapus',
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'received' => (float) $item->quantity,
                'returned' => (float) $item->returned_quantity,
                'available' => max(0, round((float) $item->quantity - (float) $item->returned_quantity, 4)),
                'unit_price' => (float) $item->unit_price,
                'track_stock' => (bool) ($item->product?->track_stock ?? false),
            ])->filter(fn ($item) => $item['available'] > 0)->values(),
        ]);
    }

    public function store(Request $request, PurchaseReturnService $service): JsonResponse
    {
        $data = $request->validate([
            'goods_receipt_id' => ['required', 'integer', 'exists:goods_receipts,id'],
            'returned_at' => ['required', 'date', 'before_or_equal:now'],
            'reason' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.goods_receipt_item_id' => ['required', 'integer', 'distinct', 'exists:goods_receipt_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0', 'max:999999999999.9999'],
        ]);

        $purchaseReturn = $service->create($data, (int) $request->user()->id);

        return response()->json([
            'message' => "Purchase Return {$purchaseReturn->number} berhasil dicatat dan stok telah diperbarui.",
            'id' => $purchaseReturn->id,
        ], 201);
    }

    public function show(PurchaseReturn $purchaseReturn, PurchaseReturnTableQuery $query): JsonResponse
    {
        $purchaseReturn = $query->details($purchaseReturn);

        return response()->json([
            'number' => $purchaseReturn->number,
            'receipt' => $purchaseReturn->goodsReceipt?->number ?? '—',
            'purchase_order' => $purchaseReturn->goodsReceipt?->purchaseOrder?->number ?? '—',
            'supplier' => $purchaseReturn->supplier?->name ?? 'Supplier dihapus',
            'returner' => $purchaseReturn->returner?->name ?? 'User dihapus',
            'returned_at' => $purchaseReturn->returned_at?->format('d/m/Y H:i') ?? '—',
            'reason' => $purchaseReturn->reason,
            'notes' => $purchaseReturn->notes ?: '—',
            'total_amount' => (float) $purchaseReturn->total_amount,
            'items' => $purchaseReturn->items->map(fn ($item) => [
                'code' => $item->product?->code ?? '—',
                'name' => $item->product?->name ?? 'Barang dihapus',
                'quantity' => number_format((float) $item->quantity, 4, ',', '.'),
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ]),
        ]);
    }

    public function pdf(PurchaseReturn $purchaseReturn, PurchaseReturnTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $purchaseReturn = $query->details($purchaseReturn);

        return Pdf::loadView('super.purchase-returns.pdf', [
            ...$branding->data(),
            'purchaseReturn' => $purchaseReturn,
        ])->setPaper('a4')->stream($this->pdfFilename($purchaseReturn->number));
    }

    public function downloadPdf(PurchaseReturn $purchaseReturn, PurchaseReturnTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $purchaseReturn = $query->details($purchaseReturn);

        return Pdf::loadView('super.purchase-returns.pdf', [
            ...$branding->data(),
            'purchaseReturn' => $purchaseReturn,
        ])->setPaper('a4')->download($this->pdfFilename($purchaseReturn->number));
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

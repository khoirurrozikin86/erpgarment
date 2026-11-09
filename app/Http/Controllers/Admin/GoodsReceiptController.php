<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Purchasing\Queries\GoodsReceiptTableQuery;
use App\Domain\Purchasing\Services\GoodsReceiptService;
use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class GoodsReceiptController extends Controller
{
    public function index()
    {
        return view('super.goods-receipts.index');
    }

    public function create()
    {
        return view('super.goods-receipts.create');
    }

    public function dt(Request $request, GoodsReceiptTableQuery $query): JsonResponse
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
            ->addColumn('purchase_order_number', fn (GoodsReceipt $receipt) => $receipt->purchaseOrder?->number ?? '—')
            ->addColumn('supplier_name', fn (GoodsReceipt $receipt) => $receipt->supplier?->name ?? 'Supplier dihapus')
            ->addColumn('receiver_name', fn (GoodsReceipt $receipt) => $receipt->receiver?->name ?? 'User dihapus')
            ->addColumn('actions', function (GoodsReceipt $receipt) {
                return '<button type="button" class="btn btn-sm btn-outline-secondary btn-view-goods-receipt" data-url="' . e(route('super.goods-receipts.show', $receipt)) . '" title="Lihat rincian"><i data-feather="eye"></i></button>'
                    . ' <a class="btn btn-sm btn-outline-primary" href="' . e(route('super.goods-receipts.pdf', $receipt)) . '" target="_blank" rel="noopener" title="Lihat PDF"><i data-feather="file-text"></i></a>'
                    . ' <a class="btn btn-sm btn-outline-secondary" href="' . e(route('super.goods-receipts.pdf.download', $receipt)) . '" title="Unduh PDF"><i data-feather="download"></i></a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function eligible(GoodsReceiptTableQuery $query)
    {
        return DataTables::eloquent($query->eligiblePurchaseOrders())
            ->addColumn('supplier_name', fn (PurchaseOrder $order) => $order->supplier?->name ?? 'Supplier dihapus')
            ->addColumn('actions', fn (PurchaseOrder $order) => '<button type="button" class="btn btn-sm btn-primary btn-select-receipt-po" data-id="' . (int) $order->id . '" data-number="' . e($order->number) . '"><i data-feather="check" class="icon-sm me-1"></i>Pilih</button>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function source(int $purchaseOrderId, GoodsReceiptTableQuery $query): JsonResponse
    {
        $order = $query->sourcePurchaseOrder($purchaseOrderId);

        return response()->json([
            'id' => $order->id,
            'number' => $order->number,
            'supplier' => $order->supplier?->name ?? 'Supplier dihapus',
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'code' => $item->product?->code ?? '—',
                'name' => $item->product?->name ?? 'Barang dihapus',
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'ordered' => (float) $item->quantity,
                'received' => (float) $item->received_quantity,
                'remaining' => max(0, round((float) $item->quantity - (float) $item->received_quantity, 4)),
                'unit_price' => (float) $item->unit_price,
                'track_stock' => (bool) ($item->product?->track_stock ?? false),
            ])->filter(fn ($item) => $item['remaining'] > 0)->values(),
        ]);
    }

    public function store(Request $request, GoodsReceiptService $service): JsonResponse
    {
        $data = $request->validate([
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'received_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'integer', 'distinct', 'exists:purchase_order_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0', 'max:999999999999.9999'],
        ]);

        $receipt = $service->receive($data, (int) $request->user()->id);

        return response()->json([
            'message' => "Penerimaan {$receipt->number} berhasil dicatat.",
            'id' => $receipt->id,
        ], 201);
    }

    public function show(GoodsReceipt $goodsReceipt, GoodsReceiptTableQuery $query): JsonResponse
    {
        $receipt = $query->details($goodsReceipt);

        return response()->json([
            'number' => $receipt->number,
            'purchase_order' => $receipt->purchaseOrder?->number ?? '—',
            'supplier' => $receipt->supplier?->name ?? 'Supplier dihapus',
            'receiver' => $receipt->receiver?->name ?? 'User dihapus',
            'received_at' => $receipt->received_at?->format('d/m/Y H:i') ?? '—',
            'notes' => $receipt->notes ?: '—',
            'items' => $receipt->items->map(fn ($item) => [
                'code' => $item->product?->code ?? '—',
                'name' => $item->product?->name ?? 'Barang dihapus',
                'quantity' => number_format((float) $item->quantity, 4, ',', '.'),
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ]),
        ]);
    }

    public function pdf(GoodsReceipt $goodsReceipt, GoodsReceiptTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $receipt = $query->details($goodsReceipt);

        return Pdf::loadView('super.goods-receipts.pdf', [
            ...$branding->data(),
            'receipt' => $receipt,
        ])->setPaper('a4')->stream($this->pdfFilename($receipt->number));
    }

    public function downloadPdf(GoodsReceipt $goodsReceipt, GoodsReceiptTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $receipt = $query->details($goodsReceipt);

        return Pdf::loadView('super.goods-receipts.pdf', [
            ...$branding->data(),
            'receipt' => $receipt,
        ])->setPaper('a4')->download($this->pdfFilename($receipt->number));
    }

    private function pdfFilename(string $number): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $number), '-_.') . '.pdf';
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Sales\Queries\DeliveryTableQuery;
use App\Domain\Sales\Services\DeliveryService;
use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\SalesOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class DeliveryController extends Controller
{
    public function index(): View { return view('super.deliveries.index'); }
    public function create(): View { return view('super.deliveries.create'); }

    public function dt(Request $request, DeliveryTableQuery $query): JsonResponse
    {
        $filters = $this->dateFilters($request);
        return DataTables::eloquent($query->builder($filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('sales_order_number', fn (Delivery $delivery) => $delivery->salesOrder?->number ?? '—')
            ->addColumn('customer_name', fn (Delivery $delivery) => $delivery->customer?->name ?? 'Customer dihapus')
            ->addColumn('deliverer_name', fn (Delivery $delivery) => $delivery->deliverer?->name ?? 'User dihapus')
            ->addColumn('status_label', fn (Delivery $delivery) => $delivery->status === 'posted' ? '<span class="badge bg-success">Diposting</span>' : '<span class="badge bg-secondary">Draft</span>')
            ->addColumn('actions', function (Delivery $delivery) {
                $buttons = '<button type="button" class="btn btn-sm btn-outline-secondary btn-view-delivery" data-url="' . e(route('super.deliveries.show', $delivery)) . '" title="Detail"><i data-feather="eye"></i></button>';
                $buttons .= ' <a class="btn btn-sm btn-outline-primary" href="' . e(route('super.deliveries.pdf', $delivery)) . '" target="_blank" rel="noopener" title="Lihat PDF"><i data-feather="file-text"></i></a>';
                $buttons .= ' <a class="btn btn-sm btn-outline-secondary" href="' . e(route('super.deliveries.pdf.download', $delivery)) . '" title="Unduh PDF"><i data-feather="download"></i></a>';
                if ($delivery->status === 'draft' && auth()->user()->can('deliveries.post')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-success btn-post-delivery" data-url="' . e(route('super.deliveries.post', $delivery)) . '" data-number="' . e($delivery->number) . '" title="Posting Delivery"><i data-feather="check"></i></button>';
                }
                if ($delivery->status === 'draft' && auth()->user()->can('deliveries.create')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-outline-danger btn-delete-delivery" data-url="' . e(route('super.deliveries.destroy', $delivery)) . '" data-number="' . e($delivery->number) . '" title="Hapus Draft"><i data-feather="trash-2"></i></button>';
                }
                return $buttons;
            })
            ->rawColumns(['status_label', 'actions'])->toJson();
    }

    public function eligible(DeliveryTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->eligibleOrders())
            ->addColumn('quotation_number', fn (SalesOrder $order) => $order->quotation?->number ?? '—')
            ->addColumn('customer_name', fn (SalesOrder $order) => $order->customer?->name ?? 'Customer dihapus')
            ->addColumn('actions', fn (SalesOrder $order) => '<button type="button" class="btn btn-sm btn-primary btn-select-delivery-order" data-id="' . (int) $order->id . '" data-number="' . e($order->number) . '"><i data-feather="check" class="icon-sm me-1"></i>Pilih</button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function source(int $salesOrderId, DeliveryTableQuery $query): JsonResponse
    {
        $order = $query->sourceOrder($salesOrderId);
        $reserved = DeliveryItem::query()
            ->join('deliveries', 'deliveries.id', '=', 'delivery_items.delivery_id')
            ->where('deliveries.sales_order_id', $order->id)
            ->where('deliveries.status', 'draft')
            ->selectRaw('delivery_items.sales_order_item_id, SUM(delivery_items.quantity) as reserved_quantity')
            ->groupBy('delivery_items.sales_order_item_id')
            ->pluck('reserved_quantity', 'sales_order_item_id');
        $items = $order->items->map(function ($item) use ($reserved) {
            $remaining = round((float) $item->quantity - (float) $item->delivered_quantity - (float) ($reserved[$item->id] ?? 0), 4);
            if ($remaining <= 0) return null;
            return [
            'sales_order_item_id' => $item->id,
            'product_id' => $item->product_id,
            'code' => $item->product?->code ?? '—',
            'name' => $item->product?->name ?? 'Barang dihapus',
            'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
            'ordered_quantity' => (float) $item->quantity,
            'delivered_quantity' => (float) $item->delivered_quantity,
            'remaining_quantity' => $remaining,
            'track_stock' => (bool) $item->product?->track_stock,
            'available_stock' => $item->product?->track_stock ? (float) ($item->product?->stock?->quantity ?? 0) : null,
        ];
        })->filter()->values();
        return response()->json([
            'id' => $order->id,
            'number' => $order->number,
            'customer' => $order->customer?->name ?? 'Customer dihapus',
            'quotation' => $order->quotation?->number ?? '—',
            'items' => $items,
        ]);
    }

    public function store(Request $request, DeliveryService $service): JsonResponse
    {
        $data = $request->validate([
            'sales_order_id' => ['required', 'integer', 'exists:sales_orders,id'],
            'delivered_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_order_item_id' => ['required', 'integer', 'distinct', 'exists:sales_order_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
        ]);
        $delivery = $service->create($data, (int) $request->user()->id);
        return response()->json(['message' => "Delivery {$delivery->number} berhasil dibuat sebagai draft. Periksa ketersediaan stok sebelum posting.", 'url' => route('super.deliveries.index')], 201);
    }

    public function show(Delivery $delivery, DeliveryTableQuery $query): JsonResponse
    {
        $delivery = $query->details($delivery);
        return response()->json([
            'number' => $delivery->number,
            'sales_order' => $delivery->salesOrder?->number ?? '—',
            'customer' => $delivery->customer?->name ?? 'Customer dihapus',
            'delivered_at' => $delivery->delivered_at?->format('d/m/Y H:i'),
            'posted_at' => $delivery->posted_at?->format('d/m/Y H:i') ?? '—',
            'status' => $delivery->status,
            'deliverer' => $delivery->deliverer?->name ?? 'User dihapus',
            'notes' => $delivery->notes ?: '—',
            'items' => $delivery->items->map(fn ($item) => [
                'code' => $item->product?->code ?? '—', 'name' => $item->product?->name ?? 'Barang dihapus',
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'quantity' => (float) $item->quantity,
            ]),
        ]);
    }

    public function post(Request $request, Delivery $delivery, DeliveryService $service): JsonResponse
    {
        $delivery = $service->post($delivery, (int) $request->user()->id);
        return response()->json(['message' => "Delivery {$delivery->number} berhasil diposting. Stok telah dikurangi dan dicatat pada Stock Card."]);
    }

    public function destroy(Delivery $delivery, DeliveryService $service): JsonResponse
    {
        $number = $delivery->number;
        $service->deleteDraft($delivery);
        return response()->json(['message' => "Draft Delivery {$number} berhasil dihapus. Sisa barang dapat dibuatkan Delivery baru."]);
    }

    public function pdf(Delivery $delivery, DeliveryTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $delivery = $query->details($delivery);
        return Pdf::loadView('super.deliveries.pdf', [...$branding->data(), 'delivery' => $delivery])->setPaper('a4')->stream($this->pdfFilename($delivery->number));
    }

    public function downloadPdf(Delivery $delivery, DeliveryTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $delivery = $query->details($delivery);
        return Pdf::loadView('super.deliveries.pdf', [...$branding->data(), 'delivery' => $delivery])->setPaper('a4')->download($this->pdfFilename($delivery->number));
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

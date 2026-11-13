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
            ->addColumn('supplier_name', fn (PurchaseReturn $purchaseReturn) => $purchaseReturn->supplier?->name ?? __('ui.modules.supplier_deleted'))
            ->addColumn('returner_name', fn (PurchaseReturn $purchaseReturn) => $purchaseReturn->returner?->name ?? __('ui.modules.user_deleted'))
            ->addColumn('actions', function (PurchaseReturn $purchaseReturn) {
                $actions = '<div class="d-inline-flex align-items-center justify-content-end gap-2 text-nowrap">'
                    . '<button type="button" class="btn btn-link btn-sm p-0 text-secondary btn-view-purchase-return" data-url="' . e(route('super.purchase-returns.show', $purchaseReturn)) . '" title="' . e(__('ui.modules.view_details')) . '" aria-label="' . e(__('ui.modules.view_details')) . '"><i data-feather="eye" class="icon-sm"></i></button>'
                    . '<a class="btn btn-link btn-sm p-0 text-primary" href="' . e(route('super.purchase-returns.pdf', $purchaseReturn)) . '" target="_blank" rel="noopener" title="' . e(__('ui.modules.view_pdf')) . '" aria-label="' . e(__('ui.modules.view_pdf')) . '"><i data-feather="file-text" class="icon-sm"></i></a>'
                    . '<a class="btn btn-link btn-sm p-0 text-secondary" href="' . e(route('super.purchase-returns.pdf.download', $purchaseReturn)) . '" title="' . e(__('ui.modules.download_pdf')) . '" aria-label="' . e(__('ui.modules.download_pdf')) . '"><i data-feather="download" class="icon-sm"></i></a>';
                if (request()->user()?->can('purchase-returns.delete')) {
                    $actions .= '<button type="button" class="btn btn-link btn-sm p-0 text-danger btn-delete-purchase-return" data-url="' . e(route('super.purchase-returns.destroy', $purchaseReturn)) . '" data-number="' . e($purchaseReturn->number) . '" title="' . e(__('ui.common.delete')) . '" aria-label="' . e(__('ui.common.delete')) . '"><i data-feather="trash-2" class="icon-sm"></i></button>';
                }
                return $actions . '</div>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function eligible(Request $request, PurchaseReturnTableQuery $query)
    {
        return DataTables::eloquent($query->eligibleGoodsReceipts())
            ->addColumn('purchase_order_number', fn (GoodsReceipt $receipt) => $receipt->purchaseOrder?->number ?? '—')
            ->addColumn('supplier_name', fn (GoodsReceipt $receipt) => $receipt->supplier?->name ?? __('ui.modules.supplier_deleted'))
            ->addColumn('actions', fn (GoodsReceipt $receipt) => '<button type="button" class="btn btn-sm btn-primary btn-select-return-receipt" data-id="' . (int) $receipt->id . '"><i data-feather="check" class="icon-sm me-1"></i>' . e(__('ui.modules.select')) . '</button>')
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
            'supplier' => $receipt->supplier?->name ?? __('ui.modules.supplier_deleted'),
            'items' => $receipt->items->map(fn ($item) => [
                'id' => $item->id,
                'code' => $item->product?->code ?? '—',
                'name' => $item->product?->name ?? __('ui.modules.item_deleted'),
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
            'message' => __('ui.modules.purchase_return_saved', ['number' => $purchaseReturn->number]),
            'id' => $purchaseReturn->id,
        ], 201);
    }

    public function destroy(Request $request, PurchaseReturn $purchaseReturn, PurchaseReturnService $service): JsonResponse
    {
        $service->delete($purchaseReturn, (int) $request->user()->id);

        return response()->json(['message' => __('ui.modules.purchase_return_deleted')]);
    }

    public function show(PurchaseReturn $purchaseReturn, PurchaseReturnTableQuery $query): JsonResponse
    {
        $purchaseReturn = $query->details($purchaseReturn);

        return response()->json([
            'number' => $purchaseReturn->number,
            'receipt' => $purchaseReturn->goodsReceipt?->number ?? '—',
            'purchase_order' => $purchaseReturn->goodsReceipt?->purchaseOrder?->number ?? '—',
            'supplier' => $purchaseReturn->supplier?->name ?? __('ui.modules.supplier_deleted'),
            'returner' => $purchaseReturn->returner?->name ?? __('ui.modules.user_deleted'),
            'returned_at' => $purchaseReturn->returned_at?->format('d/m/Y H:i') ?? '—',
            'reason' => $purchaseReturn->reason,
            'notes' => $purchaseReturn->notes ?: '—',
            'total_amount' => (float) $purchaseReturn->total_amount,
            'items' => $purchaseReturn->items->map(fn ($item) => [
                'code' => $item->product?->code ?? '—',
                'name' => $item->product?->name ?? __('ui.modules.item_deleted'),
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
                    $fail(__('ui.modules.date_end_invalid'));
                }
            }],
        ]);
    }

    private function pdfFilename(string $number): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $number), '-_.') . '.pdf';
    }
}

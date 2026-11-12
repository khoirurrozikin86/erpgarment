<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inventory\Queries\StockBalanceQuery;
use App\Exports\StockBalancesExport;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\InventoryLot;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class StockController extends Controller
{
    public function index(): View
    {
        return view('super.stocks.index');
    }

    public function dt(StockBalanceQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->builder())
            ->addColumn('category_name', fn (Product $product) => $product->category?->name ?? '—')
            ->addColumn('unit_label', fn (Product $product) => $product->unit?->symbol ?: $product->unit?->name ?: '—')
            ->addColumn('stock_quantity', fn (Product $product) => (float) ($product->stock?->quantity ?? 0))
            ->addColumn('stock_status', function (Product $product) {
                $quantity = (float) ($product->stock?->quantity ?? 0);
                $reorderPoint = (float) $product->reorder_point;
                $threshold = $reorderPoint > 0 ? $reorderPoint : (float) $product->min_stock;

                if ($quantity <= 0 && $threshold > 0) {
                    return '<span class="badge bg-danger">Habis</span>';
                }
                if ($threshold > 0 && $quantity <= $threshold) {
                    return '<span class="badge bg-warning text-dark">Stok Rendah</span>';
                }

                return '<span class="badge bg-success">Aman</span>';
            })
            ->addColumn('lot_action', fn (Product $product) => $product->track_stock
                ? '<button type="button" class="btn btn-sm btn-outline-primary view-item-lots" data-id="' . (int) $product->id . '" data-name="' . e($product->name) . '">' . e(__('ui.workflow.view_lots')) . '</button>'
                : '—')
            ->rawColumns(['stock_status', 'lot_action'])
            ->toJson();
    }

    public function lots(Product $product): JsonResponse
    {
        abort_unless($product->track_stock, 404);
        $lots = InventoryLot::query()->where('product_id', $product->id)->where('current_quantity', '>', 0)
            ->with(['supplier:id,name', 'receiptItems.goodsReceipt:id,customs_document_code,customs_document_number,customs_document_date'])
            ->orderBy('received_at')->orderBy('id')->get();

        return response()->json($lots->map(fn (InventoryLot $lot) => [
            'lot_number' => $lot->lot_number,
            'received_at' => $lot->received_at?->format('d/m/Y H:i') ?? '—',
            'supplier' => $lot->supplier?->name ?? '—',
            'initial_quantity' => number_format((float) $lot->initial_quantity, 4, ',', '.'),
            'current_quantity' => number_format((float) $lot->current_quantity, 4, ',', '.'),
            'source_type' => $lot->source_type,
            'customs_documents' => $lot->receiptItems->map(fn ($item) => $item->goodsReceipt)
                ->filter(fn ($receipt) => $receipt?->customs_document_number)
                ->unique('customs_document_number')
                ->map(fn ($receipt) => trim(($receipt->customs_document_code ?: '') . ' ' . $receipt->customs_document_number . ' ' . ($receipt->customs_document_date?->format('d/m/Y') ?? '')))
                ->implode(', ') ?: '—',
        ]));
    }

    public function export()
    {
        return Excel::download(
            new StockBalancesExport(),
            'stok-barang-' . now()->format('Y-m-d-His') . '.xlsx',
        );
    }
}

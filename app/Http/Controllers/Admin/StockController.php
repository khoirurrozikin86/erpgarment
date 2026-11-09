<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inventory\Queries\StockBalanceQuery;
use App\Exports\StockBalancesExport;
use App\Http\Controllers\Controller;
use App\Models\Product;
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
            ->rawColumns(['stock_status'])
            ->toJson();
    }

    public function export()
    {
        return Excel::download(
            new StockBalancesExport(),
            'stok-barang-' . now()->format('Y-m-d-His') . '.xlsx',
        );
    }
}

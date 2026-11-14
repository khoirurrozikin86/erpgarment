<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inventory\Queries\FinishedGoodsStockCardQuery;
use App\Exports\FinishedGoodsStockCardExport;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class FinishedGoodsStockCardController extends Controller
{
    public function index()
    {
        return view('super.finished-goods-stock-card.index');
    }

    public function productsDt(FinishedGoodsStockCardQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->products())
            ->addColumn('unit_label', fn (Product $product) => $product->unit?->symbol ?: $product->unit?->name ?: '—')
            ->addColumn('mo_number', fn () => '')
            ->addColumn('actions', fn (Product $product) => '<a class="btn btn-sm btn-primary" href="'.e(route('super.finished-goods-stock-card.show', $product)).'"><i data-feather="eye" class="icon-sm me-1"></i>Tampil</a>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function show(Product $product, FinishedGoodsStockCardQuery $query)
    {
        abort_unless($query->products()->whereKey($product->id)->exists(), 404);

        return view('super.finished-goods-stock-card.show', compact('product'));
    }

    public function movementsDt(Product $product, FinishedGoodsStockCardQuery $query): JsonResponse
    {
        abort_unless($query->products()->whereKey($product->id)->exists(), 404);

        return DataTables::eloquent($query->movements($product->id))
            ->addColumn('document_number', fn (StockMovement $movement) => $query->documentNumber($movement))
            ->addColumn('description', fn (StockMovement $movement) => $query->description($movement))
            ->addColumn('amount', fn (StockMovement $movement) => number_format(abs((float) $movement->quantity), 4, ',', '.'))
            ->addColumn('direction', fn (StockMovement $movement) => match (true) {
                (float) $movement->quantity > 0 => '+',
                (float) $movement->quantity < 0 => '-',
                default => 'S',
            })
            ->addColumn('balance', fn (StockMovement $movement) => number_format((float) $movement->quantity_after, 4, ',', '.'))
            ->addColumn('operator', fn (StockMovement $movement) => $movement->createdBy?->name ?: '—')
            ->addColumn('transaction_date', fn (StockMovement $movement) => $movement->created_at?->format('Y-m-d'))
            ->toJson();
    }

    public function exportProducts(FinishedGoodsStockCardQuery $query)
    {
        return Excel::download(new FinishedGoodsStockCardExport(products: $query->products()->get()), 'kartu-stok-barang-jadi-'.now()->format('Ymd-His').'.xlsx');
    }

    public function exportMovements(Product $product, FinishedGoodsStockCardQuery $query)
    {
        abort_unless($query->products()->whereKey($product->id)->exists(), 404);

        return Excel::download(new FinishedGoodsStockCardExport(product: $product, movements: $query->movements($product->id)->get()), 'kartu-stok-'.preg_replace('/[^A-Za-z0-9_-]/', '-', $product->code).'.xlsx');
    }
}

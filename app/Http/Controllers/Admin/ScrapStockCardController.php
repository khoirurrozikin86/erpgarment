<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inventory\Queries\ScrapStockCardQuery;
use App\Exports\ScrapStockCardExport;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class ScrapStockCardController extends Controller
{
    public function index()
    {
        return view('super.scrap-stock-card.index');
    }

    public function productsDt(ScrapStockCardQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->products())
            ->addColumn('group_label', fn () => 'SCRAP')
            ->addColumn('category_label', fn (Product $product) => $product->category?->name ?: $product->category?->code ?: '—')
            ->addColumn('unit_label', fn (Product $product) => $product->unit?->symbol ?: $product->unit?->name ?: '—')
            ->addColumn('actions', fn (Product $product) => '<a class="btn btn-sm btn-primary" href="'.e(route('super.scrap-stock-card.show', $product)).'"><i data-feather="eye" class="icon-sm me-1"></i>'.e(__('ui.scrap_stock_card.view')).'</a>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function show(Product $product, ScrapStockCardQuery $query)
    {
        abort_unless($query->products()->whereKey($product->id)->exists(), 404);

        return view('super.scrap-stock-card.show', compact('product'));
    }

    public function movementsDt(Product $product, ScrapStockCardQuery $query): JsonResponse
    {
        abort_unless($query->products()->whereKey($product->id)->exists(), 404);

        return DataTables::eloquent($query->movements($product->id))
            ->addColumn('transaction_date', fn (StockMovement $movement) => $query->transactionDate($movement))
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
            ->toJson();
    }

    public function exportMovements(Product $product, ScrapStockCardQuery $query)
    {
        abort_unless($query->products()->whereKey($product->id)->exists(), 404);

        $code = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $product->code), '-_.') ?: 'scrap';

        return Excel::download(new ScrapStockCardExport($product->id), 'kartu-stok-scrap-'.$code.'.xlsx');
    }
}

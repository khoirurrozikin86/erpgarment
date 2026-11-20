<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Queries\EquipmentStockCardQuery;
use App\Exports\EquipmentStockCardExport;
use App\Exports\EquipmentStockCardProductsExport;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class EquipmentStockCardController extends Controller
{
    public function index()
    {
        return view('super.equipment-stock-card.index');
    }

    public function productsDt(EquipmentStockCardQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->products())
            ->addColumn('category_label', fn (Product $product) => $product->category?->name ?: $product->category?->code ?: '—')
            ->addColumn('unit_label', fn (Product $product) => $product->unit?->symbol ?: $product->unit?->name ?: '—')
            ->addColumn('actions', fn (Product $product) => '<a class="btn btn-sm btn-primary" href="'.e(route('super.equipment-stock-card.show', $product)).'"><i data-feather="eye" class="icon-sm me-1"></i>'.e(__('ui.machine_stock_card.view')).'</a>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function exportProducts(EquipmentStockCardQuery $query)
    {
        return Excel::download(
            new EquipmentStockCardProductsExport($query->products()->get()),
            'daftar-mesin-peralatan-kartu-stok.xlsx',
        );
    }

    public function show(Product $product, EquipmentStockCardQuery $query)
    {
        abort_unless($query->products()->whereKey($product->id)->exists(), 404);

        return view('super.equipment-stock-card.show', compact('product'));
    }

    public function movementsDt(Product $product, EquipmentStockCardQuery $query, CompanyContext $company): JsonResponse
    {
        abort_unless($query->products()->whereKey($product->id)->exists(), 404);

        $rows = $query->movements($product->id, $company->id());

        return DataTables::collection($rows)
            ->addIndexColumn()
            ->editColumn('date', fn (array $row) => $row['date'] ?: '—')
            ->editColumn('amount', fn (array $row) => \App\Support\DisplayNumber::format($row['amount'], 2))
            ->editColumn('balance', fn (array $row) => \App\Support\DisplayNumber::format($row['balance'], 2))
            ->toJson();
    }

    public function exportMovements(Product $product, EquipmentStockCardQuery $query, CompanyContext $company)
    {
        abort_unless($query->products()->whereKey($product->id)->exists(), 404);

        $code = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $product->code), '-_.') ?: 'equipment';

        return Excel::download(
            new EquipmentStockCardExport($query->movements($product->id, $company->id())),
            'kartu-stok-mesin-'.$code.'.xlsx',
        );
    }
}

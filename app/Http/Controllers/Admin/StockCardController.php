<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inventory\Queries\StockCardQuery;
use App\Exports\StockCardExport;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class StockCardController extends Controller
{
    public function index(Request $request)
    {
        $product = null;
        if ($request->filled('product_id')) {
            $product = Product::query()
                ->where('track_stock', true)
                ->find($request->integer('product_id'));
        }

        return view('super.stock-card.index', ['initialProduct' => $product]);
    }

    public function productsDt(StockCardQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->products())
            ->addColumn('unit_label', fn(Product $product) => $product->unit?->symbol ?: $product->unit?->name ?: '—')
            ->addColumn('actions', fn(Product $product) => '<button type="button" class="btn btn-sm btn-primary btn-select-stock-product" data-id="' . (int) $product->id . '" data-code="' . e($product->code) . '" data-name="' . e($product->name) . '" data-unit="' . e($product->unit?->symbol ?: $product->unit?->name ?: '') . '"><i data-feather="check" class="icon-sm me-1"></i>Pilih</button>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function dt(Request $request, StockCardQuery $query): JsonResponse
    {
        $filters = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            ...$this->dateRules($request),
        ]);

        return DataTables::eloquent($query->movements(
            isset($filters['product_id']) ? (int) $filters['product_id'] : null,
            $filters['from_date'] ?? null,
            $filters['to_date'] ?? null,
        ))
            ->addColumn('reference_number', fn(StockMovement $movement) => $movement->goodsReceipt?->number ?? $movement->purchaseReturn?->number ?? $movement->customerReturn?->number ?? $movement->stockOpname?->number ?? $movement->delivery?->number ?? $movement->posSale?->number ?? '—')
            ->addColumn('movement_label', fn(StockMovement $movement) => match ($movement->movement_type) {
                'purchase_receipt' => '<span class="badge bg-success">Penerimaan Barang</span>',
                'purchase_return' => '<span class="badge bg-danger">Purchase Return</span>',
                'customer_return' => '<span class="badge bg-success">Customer Return</span>',
                'pos_void' => '<span class="badge bg-success">Void POS</span>',
                'stock_adjustment' => '<span class="badge bg-warning text-dark">Stock Opname</span>',
                'sales_delivery' => '<span class="badge bg-danger">Delivery</span>',
                'pos_sale' => '<span class="badge bg-danger">Penjualan POS</span>',
                default => '<span class="badge bg-secondary">' . e(ucfirst(str_replace('_', ' ', $movement->movement_type))) . '</span>',
            })
            ->addColumn('quantity_in', fn(StockMovement $movement) => (float) $movement->quantity > 0 ? number_format((float) $movement->quantity, 4, ',', '.') : '—')
            ->addColumn('quantity_out', fn(StockMovement $movement) => (float) $movement->quantity < 0 ? number_format(abs((float) $movement->quantity), 4, ',', '.') : '—')
            ->addColumn('balance_after', fn(StockMovement $movement) => number_format((float) $movement->quantity_after, 4, ',', '.'))
            ->addColumn('unit_label', fn(StockMovement $movement) => $movement->product?->unit?->symbol ?: $movement->product?->unit?->name ?: '—')
            ->rawColumns(['movement_label'])
            ->toJson();
    }

    public function summary(Request $request, StockCardQuery $query): JsonResponse
    {
        $filters = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            ...$this->dateRules($request),
        ]);
        $product = Product::query()->where('track_stock', true)->findOrFail($filters['product_id']);

        return response()->json([
            'product' => trim($product->code . ' — ' . $product->name),
            'unit' => $product->unit?->symbol ?: $product->unit?->name ?: '',
            ...$query->summary($product, $filters['from_date'] ?? null, $filters['to_date'] ?? null),
        ]);
    }

    public function export(Request $request, StockCardQuery $query)
    {
        $filters = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            ...$this->dateRules($request),
        ]);
        $product = Product::query()->where('track_stock', true)->findOrFail($filters['product_id']);

        $filenamePart = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $product->code), '-_.') ?: 'barang';

        return Excel::download(
            new StockCardExport(
                (int) $product->id,
                $filters['from_date'] ?? null,
                $filters['to_date'] ?? null,
            ),
            'stock-card-' . $filenamePart . '-' . now()->format('Y-m-d-His') . '.xlsx',
        );
    }

    private function dateRules(Request $request): array
    {
        return [
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) {
                    $fail('Tanggal akhir harus sama atau setelah tanggal awal.');
                }
            }],
        ];
    }
}

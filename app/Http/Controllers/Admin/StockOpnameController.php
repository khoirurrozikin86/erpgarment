<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inventory\Queries\StockOpnameTableQuery;
use App\Domain\Inventory\Services\StockOpnameService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockOpname;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class StockOpnameController extends Controller
{
    public function index(): View
    {
        return view('super.stock-opnames.index');
    }

    public function create(): View
    {
        return view('super.stock-opnames.create');
    }

    public function count(StockOpname $stockOpname, StockOpnameTableQuery $query): View
    {
        if (!in_array($stockOpname->status, ['draft', 'counting'], true)) {
            abort(403, 'Stock Opname ini sudah diposting.');
        }

        $stockOpname = $query->details($stockOpname);
        return view('super.stock-opnames.count', compact('stockOpname'));
    }

    public function dt(Request $request, StockOpnameTableQuery $query): JsonResponse
    {
        $filters = $this->dateFilters($request);

        return DataTables::eloquent($query->builder($filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('starter_name', fn (StockOpname $stockOpname) => $stockOpname->starter?->name ?? 'User dihapus')
            ->addColumn('counter_name', fn (StockOpname $stockOpname) => $stockOpname->counter?->name ?? '—')
            ->addColumn('poster_name', fn (StockOpname $stockOpname) => $stockOpname->poster?->name ?? '—')
            ->addColumn('status_label', fn (StockOpname $stockOpname) => match ($stockOpname->status) {
                'counting' => '<span class="badge bg-info text-dark">Menunggu Posting</span>',
                'posted' => '<span class="badge bg-success">Diposting</span>',
                default => '<span class="badge bg-secondary">Draft</span>',
            })
            ->addColumn('actions', function (StockOpname $stockOpname) {
                $buttons = '<button type="button" class="btn btn-sm btn-outline-secondary btn-view-stock-opname" data-url="' . e(route('super.stock-opnames.show', $stockOpname)) . '" title="Lihat detail"><i data-feather="eye"></i></button>';
                if (in_array($stockOpname->status, ['draft', 'counting'], true) && auth()->user()->can('stock-opnames.count')) {
                    $buttons .= ' <a class="btn btn-sm btn-outline-primary" href="' . e(route('super.stock-opnames.count', $stockOpname)) . '" title="Input hasil hitung"><i data-feather="edit-3"></i></a>';
                }
                if ($stockOpname->status === 'counting' && auth()->user()->can('stock-opnames.post')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-success btn-post-stock-opname" data-url="' . e(route('super.stock-opnames.post', $stockOpname)) . '" data-number="' . e($stockOpname->number) . '" title="Posting Opname"><i data-feather="check"></i></button>';
                }
                return $buttons;
            })
            ->rawColumns(['status_label', 'actions'])
            ->toJson();
    }

    public function productsDt(StockOpnameTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->products())
            ->addColumn('unit_label', fn (Product $product) => $product->unit?->symbol ?: $product->unit?->name ?: '—')
            ->addColumn('actions', fn (Product $product) => '<button type="button" class="btn btn-sm btn-outline-primary btn-toggle-opname-product" data-id="' . (int) $product->id . '" data-code="' . e($product->code) . '" data-name="' . e($product->name) . '" data-unit="' . e($product->unit?->symbol ?: $product->unit?->name ?: '') . '"><i data-feather="plus" class="icon-sm me-1"></i>Pilih</button>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function store(Request $request, StockOpnameService $service): JsonResponse
    {
        $data = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $stockOpname = $service->create($data, (int) $request->user()->id);

        return response()->json([
            'message' => "Stock Opname {$stockOpname->number} berhasil dibuat. Lanjutkan dengan input hasil hitung fisik.",
            'url' => route('super.stock-opnames.count', $stockOpname),
        ], 201);
    }

    public function show(StockOpname $stockOpname, StockOpnameTableQuery $query): JsonResponse
    {
        $stockOpname = $query->details($stockOpname);
        return response()->json([
            'number' => $stockOpname->number,
            'counted_at' => $stockOpname->counted_at?->format('d/m/Y H:i') ?? '—',
            'status' => $stockOpname->status,
            'starter' => $stockOpname->starter?->name ?? 'User dihapus',
            'counter' => $stockOpname->counter?->name ?? '—',
            'poster' => $stockOpname->poster?->name ?? '—',
            'posted_at' => $stockOpname->posted_at?->format('d/m/Y H:i') ?? '—',
            'notes' => $stockOpname->notes ?: '—',
            'items' => $stockOpname->items->map(fn ($item) => [
                'code' => $item->product?->code ?? '—',
                'name' => $item->product?->name ?? 'Barang dihapus',
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'system_quantity' => number_format((float) $item->system_quantity, 4, ',', '.'),
                'counted_quantity' => $item->counted_quantity === null ? 'Belum dihitung' : number_format((float) $item->counted_quantity, 4, ',', '.'),
                'difference' => $item->difference === null ? '—' : number_format((float) $item->difference, 4, ',', '.'),
            ]),
        ]);
    }

    public function saveCounts(Request $request, StockOpname $stockOpname, StockOpnameService $service): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.counted_quantity' => ['required', 'numeric', 'min:0', 'max:999999999999.9999'],
        ]);
        $stockOpname = $service->saveCounts($stockOpname, $data, (int) $request->user()->id);

        return response()->json(['message' => "Hasil hitung {$stockOpname->number} berhasil disimpan. Opname siap ditinjau dan diposting."]);
    }

    public function post(Request $request, StockOpname $stockOpname, StockOpnameService $service): JsonResponse
    {
        try {
            $stockOpname = $service->post($stockOpname, (int) $request->user()->id);
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return response()->json(['message' => "Stock Opname {$stockOpname->number} berhasil diposting. Selisih telah diperbarui ke stok."]);
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
}

<?php

namespace App\Http\Controllers\Admin;

use App\Domain\PriceLists\Services\PriceListService;
use App\Domain\PriceLists\Queries\PriceListTableQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PriceListRequest;
use App\Models\Customer;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PriceListController extends Controller
{
    public function index()
    {
        return view('super.pricelists.index', [
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->orderBy('name')->get(['id', 'code', 'name', 'is_active']),
        ]);
    }

    public function dt(PriceListTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->builder())
            ->addColumn('type_label', fn (PriceList $list) => $list->type === 'sales' ? 'Penjualan' : 'Pembelian')
            ->addColumn('party_name', fn (PriceList $list) => $list->type === 'sales' ? ($list->customer?->name ?? 'Umum') : ($list->supplier?->name ?? 'Umum'))
            ->addColumn('period', fn (PriceList $list) => ($list->valid_from?->format('d/m/Y') ?? 'Tanpa batas awal') . ' – ' . ($list->valid_until?->format('d/m/Y') ?? 'Tanpa batas akhir'))
            ->addColumn('status', fn (PriceList $list) => $list->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>')
            ->addColumn('actions', function (PriceList $list) {
                $actions = [
                    [
                        'type' => 'edit',
                        'label' => 'Edit',
                        'icon' => 'edit-2',
                        'class' => 'btn-edit-pricelist',
                        'update_url' => route('super.pricelists.update', $list),
                        'payload' => [
                            'id' => $list->id,
                            'code' => $list->code,
                            'name' => $list->name,
                            'type' => $list->type,
                            'customer_id' => $list->customer_id,
                            'supplier_id' => $list->supplier_id,
                            'valid_from' => $list->valid_from?->format('Y-m-d'),
                            'valid_until' => $list->valid_until?->format('Y-m-d'),
                            'is_active' => $list->is_active,
                            'notes' => $list->notes,
                            'items' => $list->items->map(fn ($item) => ['product_id' => $item->product_id, 'price' => $item->price])->values()->all(),
                        ],
                    ],
                    [
                        'type' => 'delete',
                        'label' => 'Hapus',
                        'icon' => 'trash-2',
                        'class' => 'btn-delete-pricelist',
                        'url' => route('super.pricelists.destroy', $list),
                        'confirm' => "Hapus pricelist {$list->name}?",
                    ],
                ];

                return view('admin.partials.table-actions', compact('actions'))->render();
            })
            ->rawColumns(['status', 'actions'])
            ->toJson();
    }

    public function store(PriceListRequest $request, PriceListService $service)
    {
        $service->create($request->validated());
        return response()->json(['message' => 'Pricelist berhasil dibuat.'], 201);
    }

    public function update(PriceListRequest $request, PriceList $priceList, PriceListService $service)
    {
        $service->update($priceList, $request->validated());
        return response()->json(['message' => 'Pricelist berhasil diperbarui.']);
    }

    public function destroy(Request $request, PriceList $priceList, PriceListService $service)
    {
        abort_unless($request->user()?->can('pricelists.delete'), 403);
        $service->delete($priceList);
        return response()->json(['message' => 'Pricelist berhasil dihapus.']);
    }
}

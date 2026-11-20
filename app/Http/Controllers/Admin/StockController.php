<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inventory\Queries\StockBalanceQuery;
use App\Domain\Inventory\Queries\StockFamilyPage;
use App\Exports\StockBalancesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockFamilyTableRequest;
use App\Models\Product;
use App\Models\InventoryLot;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class StockController extends Controller
{
    public function index(): View
    {
        return view('super.stocks.index');
    }

    public function dt(StockFamilyTableRequest $request, StockBalanceQuery $query, StockFamilyPage $families): JsonResponse
    {
        $inputs = $request->validated();
        $page = $families->get($inputs['start'], $inputs['length'], trim($inputs['search']['value'] ?? ''));
        $rows = $query->groups($page['ids']->all())->map(function (array $group) {
            $group['detail_html'] = view('super.stocks.partials.sku-details', ['group' => $group])->render();
            unset($group['details']);
            return $group;
        });

        return response()->json([
            'draw' => (int) $inputs['draw'],
            'recordsTotal' => $page['total'],
            'recordsFiltered' => $page['filtered'],
            'data' => $rows,
        ]);
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

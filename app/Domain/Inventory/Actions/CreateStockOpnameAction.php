<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Inventory\DTOs\StockOpnameData;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateStockOpnameAction
{
    public function __construct(
        private DocumentNumberService $numbers,
        private AuditLogService $auditLog,
    ) {}

    public function __invoke(StockOpnameData $data, int $userId): StockOpname
    {
        return DB::transaction(function () use ($data, $userId) {
            $productIds = collect($data->productIds)->unique()->sort()->values();
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->where('track_stock', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages(['product_ids' => 'Pilih barang yang masih melacak stok.']);
            }

            $stocks = ProductStock::query()->whereIn('product_id', $productIds)->get()->keyBy('product_id');
            $stockOpname = StockOpname::create([
                'number' => $this->numbers->generate('stock_opname'),
                'started_by' => $userId,
                'counted_at' => now(),
                'status' => 'draft',
                'notes' => $data->notes,
            ]);

            foreach ($products as $product) {
                $stockOpname->items()->create([
                    'product_id' => $product->id,
                    'system_quantity' => (float) ($stocks->get($product->id)?->quantity ?? 0),
                ]);
            }

            $this->auditLog->log(
                action: 'CREATE', module: 'STOCK_OPNAME',
                description: "Membuat Stock Opname {$stockOpname->number}",
                model: $stockOpname, oldValues: null,
                newValues: $stockOpname->load('items')->toArray(),
            );

            return $stockOpname->load(['starter', 'items.product.unit']);
        });
    }
}

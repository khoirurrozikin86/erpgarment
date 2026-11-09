<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostStockOpnameAction
{
    public function __construct(private AuditLogService $auditLog, private AutomaticJournalService $journals) {}

    public function __invoke(StockOpname $stockOpname, int $userId): StockOpname
    {
        return DB::transaction(function () use ($stockOpname, $userId) {
            $stockOpname = StockOpname::query()->lockForUpdate()->findOrFail($stockOpname->id);
            if ($stockOpname->status !== 'counting') {
                throw ValidationException::withMessages(['status' => 'Simpan hasil hitung sebelum memposting Stock Opname.']);
            }

            $items = StockOpnameItem::query()
                ->where('stock_opname_id', $stockOpname->id)
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get();
            if ($items->isEmpty() || $items->contains(fn (StockOpnameItem $item) => $item->counted_quantity === null)) {
                throw ValidationException::withMessages(['items' => 'Hasil hitung semua barang harus diisi sebelum posting.']);
            }

            $productIds = $items->pluck('product_id')->unique()->sort()->values();
            $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $oldValues = ['status' => $stockOpname->status];

            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                if (!$product) {
                    throw ValidationException::withMessages(['items' => 'Barang pada dokumen ini sudah tidak tersedia.']);
                }

                $difference = round((float) $item->counted_quantity - (float) $item->system_quantity, 4);
                $item->update(['difference' => $difference]);

                $stock = ProductStock::query()->firstOrCreate(
                    ['product_id' => $product->id],
                    ['quantity' => 0],
                );
                $stock = ProductStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
                $before = round((float) $stock->quantity, 4);
                $after = round($before + $difference, 4);
                if ($after < 0) {
                    throw ValidationException::withMessages([
                        'items' => "Saldo {$product->name} akan menjadi negatif. Periksa transaksi stok sejak opname dimulai.",
                    ]);
                }

                if ($difference !== 0.0) {
                    $averageCost = (float) $stock->average_unit_cost > 0 ? (float) $stock->average_unit_cost : (float) $product->purchase_price;
                    $costAmount = $difference > 0
                        ? round($difference * (float) $product->purchase_price, 2)
                        : round(abs($difference) * $averageCost, 2);
                    $newAverageCost = $difference > 0 && $after > 0
                        ? round((($before * $averageCost) + $costAmount) / $after, 6)
                        : $averageCost;
                    $stock->update(['quantity' => $after, 'average_unit_cost' => $newAverageCost]);
                    $movement = StockMovement::create([
                        'product_id' => $product->id,
                        'stock_opname_id' => $stockOpname->id,
                        'stock_opname_item_id' => $item->id,
                        'created_by' => $userId,
                        'movement_type' => 'stock_adjustment',
                        'quantity' => $difference,
                        'quantity_before' => $before,
                        'quantity_after' => $after,
                        'cost_amount' => $costAmount,
                        'notes' => "Penyesuaian dari Stock Opname {$stockOpname->number}",
                    ]);
                    $this->journals->recordStockMovement($movement, $userId);
                }
            }

            $stockOpname->update([
                'status' => 'posted',
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            $this->auditLog->log(
                action: 'POST', module: 'STOCK_OPNAME',
                description: "Memposting Stock Opname {$stockOpname->number}",
                model: $stockOpname,
                oldValues: $oldValues,
                newValues: [
                    'stock_opname' => $stockOpname->toArray(),
                    'items' => $items->load('product')->toArray(),
                ],
            );

            return $stockOpname->load(['starter', 'poster', 'items.product.unit']);
        });
    }
}

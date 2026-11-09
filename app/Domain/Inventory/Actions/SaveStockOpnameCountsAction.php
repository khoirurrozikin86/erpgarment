<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Inventory\DTOs\StockOpnameCountsData;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveStockOpnameCountsAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(StockOpname $stockOpname, StockOpnameCountsData $data, int $userId): StockOpname
    {
        return DB::transaction(function () use ($stockOpname, $data, $userId) {
            $stockOpname = StockOpname::query()->lockForUpdate()->findOrFail($stockOpname->id);
            if (!in_array($stockOpname->status, ['draft', 'counting'], true)) {
                throw ValidationException::withMessages(['status' => 'Stock Opname ini sudah diposting dan tidak dapat diubah.']);
            }

            $items = StockOpnameItem::query()
                ->where('stock_opname_id', $stockOpname->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');
            if ($items->count() !== count($data->items)) {
                throw ValidationException::withMessages(['items' => 'Hasil hitung harus diisi untuk semua barang dalam dokumen ini.']);
            }

            $oldValues = $items->toArray();
            foreach ($data->items as $count) {
                $item = $items->get((int) $count['product_id']);
                if (!$item) {
                    throw ValidationException::withMessages(['items' => 'Barang yang dipilih tidak termasuk dalam Stock Opname ini.']);
                }
                $counted = round((float) $count['counted_quantity'], 4);
                $item->update([
                    'counted_quantity' => $counted,
                    'difference' => round($counted - (float) $item->system_quantity, 4),
                ]);
            }

            $stockOpname->update(['status' => 'counting', 'counted_by' => $userId]);

            $this->auditLog->log(
                action: 'COUNT', module: 'STOCK_OPNAME',
                description: "Menyimpan hasil hitung Stock Opname {$stockOpname->number}",
                model: $stockOpname, oldValues: ['items' => $oldValues],
                newValues: ['items' => $stockOpname->items()->get()->toArray()],
            );

            return $stockOpname->load(['starter', 'items.product.unit']);
        });
    }
}

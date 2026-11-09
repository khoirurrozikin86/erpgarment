<?php

namespace App\Domain\PriceLists\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\PriceLists\DTOs\PriceListData;
use App\Models\PriceList;
use Illuminate\Support\Facades\DB;

class UpdatePriceListAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(PriceList $priceList, PriceListData $data): PriceList
    {
        return DB::transaction(function () use ($priceList, $data) {
            $oldValues = $priceList->load('items')->toArray();
            $attributes = $data->toArray();
            $items = $data->items;
            if ($attributes['type'] === 'sales') $attributes['supplier_id'] = null;
            else $attributes['customer_id'] = null;

            $priceList->update($attributes);
            $priceList->items()->delete();
            $priceList->items()->createMany($items);
            $priceList->refresh();

            $this->auditLog->log(
                action: 'UPDATE', module: 'PRICE_LIST',
                description: "Mengubah pricelist {$priceList->name}",
                oldValues: $oldValues, newValues: $priceList->load('items')->toArray(),
            );

            return $priceList->load(['customer', 'supplier', 'items.product']);
        });
    }
}

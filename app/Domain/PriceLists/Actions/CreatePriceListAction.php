<?php

namespace App\Domain\PriceLists\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\PriceLists\DTOs\PriceListData;
use App\Models\PriceList;
use Illuminate\Support\Facades\DB;

class CreatePriceListAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(PriceListData $data): PriceList
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toArray();
            $items = $data->items;
            $this->normalizeParty($attributes);

            $priceList = PriceList::create($attributes);
            $priceList->items()->createMany($items);

            $this->auditLog->log(
                action: 'CREATE', module: 'PRICE_LIST',
                description: "Membuat pricelist {$priceList->name}",
                oldValues: null, newValues: $priceList->toArray(),
            );

            return $priceList->load(['customer', 'supplier', 'items.product']);
        });
    }

    private function normalizeParty(array &$data): void
    {
        if ($data['type'] === 'sales') $data['supplier_id'] = null;
        else $data['customer_id'] = null;
    }
}

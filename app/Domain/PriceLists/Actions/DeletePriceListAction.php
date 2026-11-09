<?php

namespace App\Domain\PriceLists\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\PriceList;
use Illuminate\Support\Facades\DB;

class DeletePriceListAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(PriceList $priceList): bool
    {
        return DB::transaction(function () use ($priceList) {
            $oldValues = $priceList->load('items')->toArray();
            $name = $priceList->name;
            $deleted = (bool) $priceList->delete();
            if ($deleted) {
                $this->auditLog->log(
                    action: 'DELETE', module: 'PRICE_LIST',
                    description: "Menghapus pricelist {$name}",
                    oldValues: $oldValues, newValues: null,
                );
            }
            return $deleted;
        });
    }
}

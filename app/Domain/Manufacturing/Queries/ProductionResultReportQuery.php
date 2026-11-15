<?php

namespace App\Domain\Manufacturing\Queries;

use App\Models\ProductionOrderStageEntry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ProductionResultReportQuery
{
    public function builder(int $companyId, array $filters): Builder
    {
        $from = CarbonImmutable::parse($filters['from_date'])->startOfDay();
        $until = CarbonImmutable::parse($filters['to_date'])->addDay()->startOfDay();
        return ProductionOrderStageEntry::query()->where('stage', 'packing')
            ->where('processed_at', '>=', $from)->where('processed_at', '<', $until)
            ->whereHas('productionOrder', fn (Builder $q) => $q->where('company_id', $companyId))
            ->with(['productionOrder:id,number,buyer_po_number,output_product_id', 'productionOrder.outputProduct:id,code,name,unit_id', 'productionOrder.outputProduct.unit:id,name,symbol'])
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $q) use ($search) {
                    $q->where('packing_number', 'like', "%{$search}%")
                        ->orWhereHas('productionOrder', fn (Builder $q) => $q->where('number', 'like', "%{$search}%")->orWhere('buyer_po_number', 'like', "%{$search}%"))
                        ->orWhereHas('productionOrder.outputProduct', fn (Builder $q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
                });
            })->orderBy('processed_at')->orderBy('id');
    }
}

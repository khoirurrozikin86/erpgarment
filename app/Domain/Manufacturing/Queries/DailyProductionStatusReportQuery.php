<?php

namespace App\Domain\Manufacturing\Queries;

use App\Models\ProductionOrder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class DailyProductionStatusReportQuery
{
    private const STAGES = ['cutting', 'sewing', 'packing', 'finishing'];

    public function builder(int $companyId, array $filters): Builder
    {
        $from = CarbonImmutable::parse($filters['from_date'])->startOfDay();
        $until = CarbonImmutable::parse($filters['to_date'])->startOfDay()->addDay();
        $inPeriod = fn (Builder $entries) => $entries
            ->where('processed_at', '>=', $from)
            ->where('processed_at', '<', $until)
            ->whereIn('stage', self::STAGES);

        $query = ProductionOrder::query()->where('company_id', $companyId)
            ->whereHas('stageEntries', $inPeriod)
            ->with(['buyer:id,name', 'outputProduct:id,code,name,style_code,color,size,parent_product_id', 'outputProduct.parentProduct:id,code'])
            ->select(['id', 'company_id', 'number', 'buyer_po_number', 'customer_id', 'output_product_id', 'style_code', 'order_color', 'order_size'])
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $q) use ($search) {
                    $q->where('number', 'like', "%{$search}%")->orWhere('buyer_po_number', 'like', "%{$search}%")
                        ->orWhere('style_code', 'like', "%{$search}%")
                        ->orWhere('order_color', 'like', "%{$search}%")
                        ->orWhere('order_size', 'like', "%{$search}%")
                        ->orWhereHas('buyer', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('outputProduct', fn (Builder $q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhereHas('parentProduct', fn (Builder $parent) => $parent->where('code', 'like', "%{$search}%"))->orWhere('color', 'like', "%{$search}%")->orWhere('size', 'like', "%{$search}%")->orWhere('style_code', 'like', "%{$search}%"));
                });
            });
        foreach (self::STAGES as $stage) {
            $query->withSum([
                'stageEntries as '.$stage.'_quantity' => fn (Builder $entries) => $inPeriod($entries)->where('stage', $stage),
            ], 'good_quantity');
        }

        return $query->orderBy('customer_id')->orderBy('number')->orderBy('id');
    }

    public static function values(ProductionOrder $row): array
    {
        return array_map(fn (string $stage) => (float) ($row->{$stage.'_quantity'} ?? 0), self::STAGES);
    }
}

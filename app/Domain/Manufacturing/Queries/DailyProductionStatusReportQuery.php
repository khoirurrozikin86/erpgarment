<?php

namespace App\Domain\Manufacturing\Queries;

use App\Models\{GoodsSentNoteItem, ProductionOrder};
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class DailyProductionStatusReportQuery
{
    public function builder(int $companyId, array $filters): Builder
    {
        $day = CarbonImmutable::parse($filters['to_date'])->startOfDay();
        $until = $day->addDay();
        $query = ProductionOrder::query()->where('company_id', $companyId)
            ->where('po_date', '>=', $filters['from_date'])->where('po_date', '<=', $filters['to_date'])
            ->with(['buyer:id,name', 'outputProduct:id,code,name,style_code'])
            ->select(['id', 'company_id', 'number', 'buyer_po_number', 'customer_id', 'output_product_id', 'planned_quantity'])
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $q) use ($search) {
                    $q->where('number', 'like', "%{$search}%")->orWhere('buyer_po_number', 'like', "%{$search}%")
                        ->orWhereHas('buyer', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('outputProduct', fn (Builder $q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('style_code', 'like', "%{$search}%"));
                });
            });
        foreach (['cutting', 'sewing', 'finishing'] as $stage) {
            $query->withSum(['stageEntries as '.$stage.'_today' => fn (Builder $q) => $q->where('stage', $stage)->where('processed_at', '>=', $day)->where('processed_at', '<', $until)], 'good_quantity')
                ->withSum(['stageEntries as '.$stage.'_total' => fn (Builder $q) => $q->where('stage', $stage)->where('processed_at', '<', $until)], 'good_quantity');
        }
        foreach (['today', 'total'] as $period) {
            $delivery = GoodsSentNoteItem::query()->selectRaw('SUM(quantity)')
                ->whereColumn('production_order_id', 'production_orders.id')->whereColumn('product_id', 'production_orders.output_product_id')
                ->whereHas('note', function (Builder $q) use ($companyId, $filters, $period) {
                    $q->where('company_id', $companyId)->whereNotNull('approved_at')->where('sent_date', '<=', $filters['to_date']);
                    if ($period === 'today') $q->where('sent_date', '>=', $filters['to_date']);
                });
            $query->addSelect(['delivery_'.$period => $delivery]);
        }
        return $query->orderBy('customer_id')->orderBy('number')->orderBy('id');
    }

    public static function values(ProductionOrder $row): array
    {
        $cut = (float) ($row->cutting_total ?? 0);
        $sew = (float) ($row->sewing_total ?? 0);
        $finish = (float) ($row->finishing_total ?? 0);
        $delivery = (float) ($row->delivery_total ?? 0);
        return [
            (float) ($row->cutting_today ?? 0), $cut, round($cut - (float) $row->planned_quantity, 4),
            (float) ($row->sewing_today ?? 0), $sew, round($sew - $cut, 4),
            (float) ($row->finishing_today ?? 0), $finish, round($finish - $sew, 4),
            (float) ($row->delivery_today ?? 0), $delivery,
        ];
    }
}

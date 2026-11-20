<?php

namespace App\Domain\Manufacturing\Queries;

use App\Models\GoodsSentNoteItem;
use App\Models\ProductionOrder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ProductionPlanReportQuery
{
    public function builder(int $companyId, array $filters): Builder
    {
        $until = CarbonImmutable::parse($filters['to_date'])->addDay()->startOfDay();
        $shipped = GoodsSentNoteItem::query()->selectRaw('SUM(quantity)')
            ->whereColumn('production_order_id', 'production_orders.id')
            ->whereColumn('product_id', 'production_orders.output_product_id')
            ->whereHas('note', fn (Builder $note) => $note->where('company_id', $companyId)
                ->whereNotNull('approved_at')->where('sent_date', '<=', $filters['to_date']));

        return ProductionOrder::query()->where('company_id', $companyId)
            ->where('po_date', '>=', $filters['from_date'])->where('po_date', '<=', $filters['to_date'])
            ->with('outputProduct:id,code,name,color,size')
            ->select(['id', 'number', 'po_date', 'output_product_id', 'planned_quantity'])
            ->withSum(['stageEntries as report_rejected' => fn (Builder $q) => $q->where('processed_at', '<', $until)], 'rejected_quantity')
            ->withSum(['stageEntries as report_produced' => fn (Builder $q) => $q->where('stage', 'finishing')->where('processed_at', '<', $until)], 'good_quantity')
            ->addSelect(['report_shipped' => $shipped])
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $q) use ($search) {
                    $q->where('number', 'like', "%{$search}%")
                        ->orWhereHas('outputProduct', fn (Builder $q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('color', 'like', "%{$search}%")->orWhere('size', 'like', "%{$search}%"));
                });
            })->orderBy('number')->orderBy('id');
    }
}

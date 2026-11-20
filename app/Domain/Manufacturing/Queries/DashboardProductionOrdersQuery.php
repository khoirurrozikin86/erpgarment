<?php

namespace App\Domain\Manufacturing\Queries;

use App\Models\{ProductionOrder, ProductionOrderStageEntry};
use Illuminate\Support\Facades\DB;

class DashboardProductionOrdersQuery
{
    public function forPeriod(int $companyId, array $filters): array
    {
        $production = ProductionOrderStageEntry::query()
            ->whereHas('productionOrder', fn ($query) => $query->where('company_id', $companyId))
            ->whereIn('stage', ProductionOrderProgressQuery::STAGES)
            ->select('production_order_id')
            ->selectRaw("SUM(CASE WHEN stage = 'finishing' THEN good_quantity ELSE 0 END) AS finished_quantity")
            ->groupBy('production_order_id');

        $orders = ProductionOrder::query()
            ->where('production_orders.company_id', $companyId)
            ->leftJoinSub($production, 'production', 'production.production_order_id', '=', 'production_orders.id')
            ->select('production_orders.number')
            ->selectRaw('MIN(production_orders.id) AS id, MAX(production_orders.id) AS latest_id')
            ->selectRaw("SUM(CASE WHEN status <> 'cancelled' THEN planned_quantity ELSE 0 END) AS target_quantity")
            ->selectRaw("SUM(CASE WHEN status <> 'cancelled' THEN COALESCE(production.finished_quantity, 0) ELSE 0 END) AS finished_quantity")
            ->selectRaw("CASE
                WHEN SUM(CASE WHEN status <> 'cancelled' THEN 1 ELSE 0 END) = 0 THEN 'cancelled'
                WHEN SUM(CASE WHEN status <> 'cancelled' AND
                    (planned_quantity <= 0 OR COALESCE(production.finished_quantity, 0) < planned_quantity) THEN 1 ELSE 0 END) = 0 THEN 'completed'
                WHEN SUM(CASE WHEN status <> 'cancelled' AND
                    (production.production_order_id IS NOT NULL OR status IN ('in_progress', 'completed')) THEN 1 ELSE 0 END) > 0 THEN 'in_progress'
                WHEN SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) > 0 THEN 'draft'
                ELSE 'released'
                END AS progress_status")
            ->groupBy('production_orders.production_order_header_id', 'production_orders.number')
            ->havingRaw('MIN(production_orders.created_at) >= ?', [$filters['from_date'].' 00:00:00'])
            ->havingRaw('MIN(production_orders.created_at) < ?', [
                \Illuminate\Support\Carbon::parse($filters['to_date'])->addDay()->toDateString().' 00:00:00',
            ]);

        $counts = DB::query()->fromSub(clone $orders, 'orders')->select('progress_status')
            ->selectRaw('COUNT(*) AS total')->groupBy('progress_status')->pluck('total', 'progress_status');
        $recent = (clone $orders)->orderByDesc('latest_id')->limit(10)->get();
        $details = ProductionOrder::with('buyer:id,name')->whereKey($recent->pluck('id')->all())->get()->keyBy('id');
        $recent->each(fn ($order) => $order->setRelation('buyer', $details->get($order->id)?->buyer));

        return ['counts' => $counts, 'recent' => $recent];
    }
}

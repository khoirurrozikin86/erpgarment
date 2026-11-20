<?php

namespace App\Domain\Manufacturing\Queries;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class WipPositionReportQuery
{
    public function builder(int $companyId, array $filters): Builder
    {
        $from = CarbonImmutable::parse($filters['from_date'])->startOfDay();
        $until = CarbonImmutable::parse($filters['to_date'])->addDay()->startOfDay();
        $events = null;
        // PKG tracks the handoff to finishing. This report does not post inventory.
        foreach ([
            ['cutting', 'CUT', 'good_quantity', 1], ['sewing', 'CUT', 'input_quantity', -1],
            ['sewing', 'SEW', 'good_quantity', 1], ['packing', 'SEW', 'input_quantity', -1],
            ['packing', 'PKG', 'good_quantity', 1], ['finishing', 'PKG', 'input_quantity', -1],
        ] as [$stage, $position, $column, $sign]) {
            $part = DB::table('production_order_stage_entries as e')
                ->join('production_orders as o', 'o.id', '=', 'e.production_order_id')
                ->join('products as p', 'p.id', '=', 'o.output_product_id')
                ->leftJoin('products as parent', 'parent.id', '=', 'p.parent_product_id')
                ->leftJoin('categories as category', 'category.id', '=', 'p.category_id')
                ->leftJoin('units as u', 'u.id', '=', 'p.unit_id')
                ->where('o.company_id', $companyId)->whereNull('e.deleted_at')
                ->where('e.stage', $stage)->where('e.processed_at', '<', $until)
                ->select(['o.id as production_order_id', 'o.number as mo_number', 'o.planned_quantity', 'p.id as product_id', 'p.code as sku_code', 'p.name', 'category.name as category_name', 'category.code as category_code', 'u.symbol', 'u.name as unit_name'])
                ->selectRaw('COALESCE(parent.code, p.code) as code')
                ->selectRaw('COALESCE(o.style_code, p.style_code) as style_code, COALESCE(o.order_color, p.color) as color, COALESCE(o.order_size, p.size) as size')
                ->selectRaw('? as position', [$position])
                ->selectRaw("e.{$column} * ? as quantity", [$sign])
                ->selectRaw('CASE WHEN e.processed_at >= ? THEN 1 ELSE 0 END as period_activity', [$from]);
            $events = $events ? $events->unionAll($part) : $part;
        }
        return DB::query()->fromSub($events, 'wip_events')
            ->select(['production_order_id', 'mo_number', 'planned_quantity', 'product_id', 'code', 'name', 'category_name', 'style_code', 'color', 'size', 'symbol', 'unit_name', 'position'])
            ->selectRaw('SUM(quantity) as quantity')
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $q) use ($search) {
                    $q->where('mo_number', 'like', "%{$search}%")->orWhere('style_code', 'like', "%{$search}%")->orWhere('color', 'like', "%{$search}%")->orWhere('size', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('sku_code', 'like', "%{$search}%")
                        ->orWhere('category_name', 'like', "%{$search}%")->orWhere('category_code', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(code, '/', position) LIKE ?", ["%{$search}%"]);
                });
            })
            ->groupBy('production_order_id', 'mo_number', 'planned_quantity', 'product_id', 'code', 'name', 'category_name', 'style_code', 'color', 'size', 'symbol', 'unit_name', 'position')
            ->havingRaw('SUM(quantity) <> 0 OR SUM(period_activity) > 0')
            ->orderBy('code')->orderByRaw("CASE position WHEN 'CUT' THEN 1 WHEN 'SEW' THEN 2 ELSE 3 END")->orderBy('product_id');
    }
}

<?php

namespace App\Domain\Manufacturing\Queries;

use App\Models\ProductionOrder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ProductionStageProgressQuery
{
    public function builder(int $companyId, string $from, string $to, string $previousStage, string $stage): Builder
    {
        $until = CarbonImmutable::parse($to)->addDay()->toDateString();
        $throughDate = fn (Builder $entries, string $name) => $entries->where('stage', $name)->where('processed_at', '<', $until);

        return ProductionOrder::query()->where('company_id', $companyId)
            ->whereHas('stageEntries', fn (Builder $entries) => $entries
                ->whereIn('stage', [$previousStage, $stage])->where('processed_at', '>=', $from)->where('processed_at', '<', $until))
            ->select(['id', 'number', 'output_product_id', 'order_color', 'order_size', 'planned_quantity'])
            ->with('outputProduct:id,color,size')
            ->withSum(['stageEntries as incoming_quantity' => fn (Builder $entries) => $throughDate($entries, $previousStage)], 'good_quantity')
            ->withSum(['stageEntries as processed_quantity' => fn (Builder $entries) => $throughDate($entries, $stage)], 'input_quantity')
            ->withSum(['stageEntries as output_quantity' => fn (Builder $entries) => $throughDate($entries, $stage)], 'good_quantity')
            ->orderBy('number')->orderBy('id');
    }
}

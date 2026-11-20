<?php

namespace App\Domain\Manufacturing\Services;

use App\Models\ProductionOrder;

/** Balances belong to one MO detail, never to the shared MO number. */
class ProductionStageBalance
{
    public const STAGES = ['cutting', 'sewing', 'packing', 'finishing'];

    public function remaining(ProductionOrder $order, string $stage, ?int $exceptId = null): float
    {
        $index = array_search($stage, self::STAGES, true);
        if ($index === false) throw new \InvalidArgumentException('Unknown production stage.');

        $entries = $order->stageEntries();
        $available = $index === 0 ? (float) $order->planned_quantity
            : (float) (clone $entries)->where('stage', self::STAGES[$index - 1])->sum('good_quantity');
        $used = (clone $entries)->where('stage', $stage)
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId));
        // Cutting input is fabric (MTR/KG), whereas its output and the MO are pieces.
        $processed = $stage === 'cutting'
            ? (float) (clone $used)->sum('good_quantity') + (float) (clone $used)->sum('rejected_quantity')
            : (float) $used->sum('input_quantity');

        return round(max(0, $available - $processed), 4);
    }
}

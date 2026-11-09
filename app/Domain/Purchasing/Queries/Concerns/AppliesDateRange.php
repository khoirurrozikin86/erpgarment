<?php

namespace App\Domain\Purchasing\Queries\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

trait AppliesDateRange
{
    protected function applyDateRange(Builder $query, string $column, ?string $fromDate, ?string $toDate): Builder
    {
        if ($fromDate) {
            $query->where($column, '>=', CarbonImmutable::parse($fromDate)->startOfDay());
        }

        if ($toDate) {
            $query->where($column, '<', CarbonImmutable::parse($toDate)->addDay()->startOfDay());
        }

        return $query;
    }
}

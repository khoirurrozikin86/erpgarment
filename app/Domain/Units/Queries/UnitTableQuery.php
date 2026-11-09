<?php

namespace App\Domain\Units\Queries;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;

class UnitTableQuery
{
    public function builder(): Builder
    {
        return Unit::query()
            ->select([
                'id',
                'code',
                'name',
                'symbol',
                'description',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->orderByDesc('created_at');
    }
}

<?php

namespace App\Domain\Styles\Queries;

use App\Models\Style;
use Illuminate\Database\Eloquent\Builder;

class StyleTableQuery
{
    public function builder(): Builder
    {
        return Style::query()->select(['id', 'code', 'name', 'created_at'])->orderBy('code');
    }
}

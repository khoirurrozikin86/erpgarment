<?php

namespace App\Domain\Categories\Queries;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;

class CategoryTableQuery
{
    public function builder(): Builder
    {
        return Category::query()
            ->select([
                'id',
                'code',
                'name',
                'description',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->orderByDesc('created_at');
    }
}

<?php

namespace App\Domain\Categories\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Categories\DTOs\CategoryData;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CreateCategoryAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function __invoke(CategoryData $data): Category
    {
        return DB::transaction(function () use ($data) {

            $category = Category::create(
                $data->toArray()
            );

            $this->auditLog->log(
                action: 'CREATE',
                module: 'CATEGORY',
                description: "Membuat kategori {$category->name}",
                oldValues: null,
                newValues: $category->toArray(),
            );

            return $category;
        });
    }
}

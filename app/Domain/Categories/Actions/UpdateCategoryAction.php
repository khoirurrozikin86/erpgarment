<?php

namespace App\Domain\Categories\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Categories\DTOs\CategoryData;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class UpdateCategoryAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function __invoke(
        Category $category,
        CategoryData $data
    ): Category {
        return DB::transaction(function () use ($category, $data) {

            $oldValues = $category->getOriginal();

            $category->update(
                $data->toArray()
            );

            $category->refresh();

            $this->auditLog->log(
                action: 'UPDATE',
                module: 'CATEGORY',
                description: "Mengubah kategori {$category->name}",
                oldValues: $oldValues,
                newValues: $category->toArray(),
            );

            return $category;
        });
    }
}

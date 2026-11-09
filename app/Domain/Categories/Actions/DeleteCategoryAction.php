<?php

namespace App\Domain\Categories\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class DeleteCategoryAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function __invoke(Category $category): bool
    {
        return DB::transaction(function () use ($category) {

            $oldValues = $category->getOriginal();

            $categoryName = $category->name;

            $deleted = $category->delete();

            if ($deleted) {

                $this->auditLog->log(
                    action: 'DELETE',
                    module: 'CATEGORY',
                    description: "Menghapus kategori {$categoryName}",
                    oldValues: $oldValues,
                    newValues: null,
                );
            }

            return $deleted;
        });
    }
}

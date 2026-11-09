<?php

namespace App\Domain\Products\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DeleteProductAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function __invoke(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            $oldValues = $product->toArray();

            $productName = $product->name;

            $deleted = $product->delete();

            if ($deleted) {
                $this->auditLog->log(
                    action: 'DELETE',
                    module: 'PRODUCT',
                    description: "Menghapus barang {$productName}",
                    oldValues: $oldValues,
                    newValues: null,
                );
            }

            return $deleted;
        });
    }
}

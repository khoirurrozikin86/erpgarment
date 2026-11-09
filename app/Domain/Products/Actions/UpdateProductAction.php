<?php

namespace App\Domain\Products\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Products\DTOs\ProductData;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class UpdateProductAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function __invoke(
        Product $product,
        ProductData $data
    ): Product {
        return DB::transaction(function () use ($product, $data) {
            $oldValues = $product->toArray();

            $product->update($data->toArray());

            $product->refresh();

            $this->auditLog->log(
                action: 'UPDATE',
                module: 'PRODUCT',
                description: "Mengubah barang {$product->name}",
                oldValues: $oldValues,
                newValues: $product->toArray(),
            );

            return $product;
        });
    }
}

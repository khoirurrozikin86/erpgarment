<?php

namespace App\Domain\Products\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Products\DTOs\ProductData;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CreateProductAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function __invoke(ProductData $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create($data->toArray());

            $this->auditLog->log(
                action: 'CREATE',
                module: 'PRODUCT',
                description: "Membuat barang {$product->name}",
                oldValues: null,
                newValues: $product->toArray(),
            );

            return $product;
        });
    }
}

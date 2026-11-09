<?php

namespace App\Domain\Products\Services;

use App\Domain\Products\Actions\CreateProductAction;
use App\Domain\Products\Actions\DeleteProductAction;
use App\Domain\Products\Actions\UpdateProductAction;
use App\Domain\Products\DTOs\ProductData;
use App\Models\Product;

class ProductService
{
    public function __construct(
        protected CreateProductAction $create,
        protected UpdateProductAction $update,
        protected DeleteProductAction $delete,
    ) {}

    public function create(array $payload): Product
    {
        return ($this->create)(
            ProductData::fromArray($payload)
        );
    }

    public function update(
        Product $product,
        array $payload
    ): Product {
        return ($this->update)(
            $product,
            ProductData::fromArray($payload)
        );
    }

    public function delete(Product $product): bool
    {
        return ($this->delete)($product);
    }
}

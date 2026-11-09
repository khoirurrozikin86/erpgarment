<?php

namespace App\Domain\Suppliers\Services;

use App\Domain\Suppliers\Actions\CreateSupplierAction;
use App\Domain\Suppliers\Actions\DeleteSupplierAction;
use App\Domain\Suppliers\Actions\UpdateSupplierAction;
use App\Domain\Suppliers\DTOs\SupplierData;
use App\Models\Supplier;

class SupplierService
{
    public function __construct(
        protected CreateSupplierAction $create,
        protected UpdateSupplierAction $update,
        protected DeleteSupplierAction $delete,
    ) {}

    public function create(array $payload): Supplier
    {
        return ($this->create)(
            SupplierData::fromArray($payload)
        );
    }

    public function update(
        Supplier $supplier,
        array $payload
    ): Supplier {
        return ($this->update)(
            $supplier,
            SupplierData::fromArray($payload)
        );
    }

    public function delete(Supplier $supplier): bool
    {
        return ($this->delete)($supplier);
    }
}

<?php

namespace App\Domain\Customers\Services;

use App\Domain\Customers\Actions\CreateCustomerAction;
use App\Domain\Customers\Actions\DeleteCustomerAction;
use App\Domain\Customers\Actions\UpdateCustomerAction;
use App\Domain\Customers\DTOs\CustomerData;
use App\Models\Customer;

class CustomerService
{
    public function __construct(
        protected CreateCustomerAction $create,
        protected UpdateCustomerAction $update,
        protected DeleteCustomerAction $delete,
    ) {}

    public function create(array $payload): Customer
    {
        return ($this->create)(
            CustomerData::fromArray($payload)
        );
    }

    public function update(
        Customer $customer,
        array $payload
    ): Customer {
        return ($this->update)(
            $customer,
            CustomerData::fromArray($payload)
        );
    }

    public function delete(Customer $customer): bool
    {
        return ($this->delete)($customer);
    }
}

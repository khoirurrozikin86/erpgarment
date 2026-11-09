<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Customers\DTOs\CustomerData;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CreateCustomerAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function __invoke(CustomerData $data): Customer
    {
        return DB::transaction(function () use ($data) {

            $customer = Customer::create(
                $data->toArray()
            );

            $this->auditLog->log(
                action: 'CREATE',
                module: 'CUSTOMER',
                description: "Membuat customer {$customer->name}",
                oldValues: null,
                newValues: $customer->toArray(),
            );

            return $customer;
        });
    }
}

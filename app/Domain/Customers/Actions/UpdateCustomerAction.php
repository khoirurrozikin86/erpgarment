<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Customers\DTOs\CustomerData;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class UpdateCustomerAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function __invoke(
        Customer $customer,
        CustomerData $data
    ): Customer {
        return DB::transaction(function () use ($customer, $data) {

            $oldValues = $customer->toArray();

            $customer->update(
                $data->toArray()
            );

            $customer->refresh();

            $this->auditLog->log(
                action: 'UPDATE',
                module: 'CUSTOMER',
                description: "Mengubah customer {$customer->name}",
                oldValues: $oldValues,
                newValues: $customer->toArray(),
            );

            return $customer;
        });
    }
}

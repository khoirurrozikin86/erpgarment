<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class DeleteCustomerAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function __invoke(Customer $customer): bool
    {
        return DB::transaction(function () use ($customer) {

            $oldValues = $customer->toArray();

            $deleted = $customer->delete();

            if ($deleted) {
                $this->auditLog->log(
                    action: 'DELETE',
                    module: 'CUSTOMER',
                    description: "Menghapus customer {$customer->name}",
                    oldValues: $oldValues,
                    newValues: null,
                );
            }

            return $deleted;
        });
    }
}

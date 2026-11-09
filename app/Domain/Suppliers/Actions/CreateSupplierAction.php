<?php

namespace App\Domain\Suppliers\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Suppliers\DTOs\SupplierData;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class CreateSupplierAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function __invoke(SupplierData $data): Supplier
    {
        return DB::transaction(function () use ($data) {

            $supplier = Supplier::create(
                $data->toArray()
            );

            $this->auditLog->log(
                action: 'CREATE',
                module: 'SUPPLIER',
                description: "Membuat supplier {$supplier->name}",
                oldValues: null,
                newValues: $supplier->toArray(),
            );

            return $supplier;
        });
    }
}

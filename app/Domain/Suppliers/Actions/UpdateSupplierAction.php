<?php

namespace App\Domain\Suppliers\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Suppliers\DTOs\SupplierData;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class UpdateSupplierAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function __invoke(
        Supplier $supplier,
        SupplierData $data
    ): Supplier {
        return DB::transaction(function () use ($supplier, $data) {

            $oldValues = $supplier->toArray();

            $supplier->update(
                $data->toArray()
            );

            $supplier->refresh();

            $this->auditLog->log(
                action: 'UPDATE',
                module: 'SUPPLIER',
                description: "Mengubah supplier {$supplier->name}",
                oldValues: $oldValues,
                newValues: $supplier->toArray(),
            );

            return $supplier;
        });
    }
}

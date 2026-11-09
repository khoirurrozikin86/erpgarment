<?php

namespace App\Domain\Suppliers\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class DeleteSupplierAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function __invoke(Supplier $supplier): bool
    {
        return DB::transaction(function () use ($supplier) {

            $oldValues = $supplier->toArray();

            $deleted = $supplier->delete();

            if ($deleted) {
                $this->auditLog->log(
                    action: 'DELETE',
                    module: 'SUPPLIER',
                    description: "Menghapus supplier {$supplier->name}",
                    oldValues: $oldValues,
                    newValues: null,
                );
            }

            return $deleted;
        });
    }
}

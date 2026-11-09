<?php

namespace App\Domain\Units\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class DeleteUnitAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function __invoke(Unit $unit): bool
    {
        return DB::transaction(function () use ($unit) {

            $oldValues = $unit->toArray();

            $deleted = $unit->delete();

            if ($deleted) {
                $this->auditLog->log(
                    action: 'DELETE',
                    module: 'UNIT',
                    description: "Menghapus satuan {$unit->name}",
                    oldValues: $oldValues,
                    newValues: null,
                );
            }

            return $deleted;
        });
    }
}

<?php

namespace App\Domain\Units\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Units\DTOs\UnitData;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class UpdateUnitAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function __invoke(Unit $unit, UnitData $data): Unit
    {
        return DB::transaction(function () use ($unit, $data) {

            $oldValues = $unit->toArray();

            $unit->update($data->toArray());

            $unit->refresh();

            $this->auditLog->log(
                action: 'UPDATE',
                module: 'UNIT',
                description: "Mengubah satuan {$unit->name}",
                oldValues: $oldValues,
                newValues: $unit->toArray(),
            );

            return $unit;
        });
    }
}

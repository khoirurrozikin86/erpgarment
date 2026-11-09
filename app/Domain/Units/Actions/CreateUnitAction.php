<?php

namespace App\Domain\Units\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Units\DTOs\UnitData;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class CreateUnitAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function __invoke(UnitData $data): Unit
    {
        return DB::transaction(function () use ($data) {

            $unit = Unit::create($data->toArray());

            $this->auditLog->log(
                action: 'CREATE',
                module: 'UNIT',
                description: "Membuat satuan {$unit->name}",
                oldValues: null,
                newValues: $unit->toArray(),
            );

            return $unit;
        });
    }
}

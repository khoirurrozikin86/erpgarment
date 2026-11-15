<?php

namespace App\Domain\Subcontract\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\Subcontractor;
use Illuminate\Support\Facades\DB;

class SaveSubcontractAction
{
    public function create(array $data, int $companyId, int $userId): Subcontractor
    {
        return DB::transaction(function () use ($data, $companyId, $userId) {
            $record = Subcontractor::create($data + ['company_id' => $companyId]);
            app(AuditLogService::class)->log('CREATE', 'SUBCONTRACTOR', "Created subcontractor {$record->name}", $record, null, $record->only(['name', 'is_active']), $userId);
            return $record;
        });
    }

    public function update(Subcontractor $record, array $data, int $userId): Subcontractor
    {
        return DB::transaction(function () use ($record, $data, $userId) {
            $old = $record->only(['name', 'is_active']); $record->update($data);
            app(AuditLogService::class)->log('UPDATE', 'SUBCONTRACTOR', "Updated subcontractor {$record->name}", $record, $old, $record->only(['name', 'is_active']), $userId);
            return $record;
        });
    }

    public function delete(Subcontractor $record, int $userId): void
    {
        DB::transaction(function () use ($record, $userId) {
            $old = $record->only(['name', 'is_active']); $name = $record->name; $record->delete();
            app(AuditLogService::class)->log('DELETE', 'SUBCONTRACTOR', "Deleted subcontractor {$name}", $record, $old, null, $userId);
        });
    }
}

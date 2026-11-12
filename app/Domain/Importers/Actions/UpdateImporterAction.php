<?php

namespace App\Domain\Importers\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Importers\DTOs\ImporterData;
use App\Models\Importer;
use Illuminate\Support\Facades\DB;

class UpdateImporterAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(Importer $importer, ImporterData $data): Importer
    {
        return DB::transaction(function () use ($importer, $data) {
            $oldValues = $importer->getOriginal();
            $importer->update($data->toArray());
            $importer->refresh();
            $this->auditLog->log(action: 'UPDATE', module: 'IMPORTER', description: "Mengubah importir {$importer->name}", oldValues: $oldValues, newValues: $importer->toArray());

            return $importer;
        });
    }
}

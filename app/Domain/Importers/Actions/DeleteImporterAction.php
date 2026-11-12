<?php

namespace App\Domain\Importers\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\Importer;
use Illuminate\Support\Facades\DB;

class DeleteImporterAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(Importer $importer): bool
    {
        return DB::transaction(function () use ($importer) {
            $oldValues = $importer->getOriginal();
            $name = $importer->name;
            $deleted = $importer->delete();
            if ($deleted) {
                $this->auditLog->log(action: 'DELETE', module: 'IMPORTER', description: "Menghapus importir {$name}", oldValues: $oldValues);
            }

            return $deleted;
        });
    }
}

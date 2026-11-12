<?php

namespace App\Domain\Importers\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Importers\DTOs\ImporterData;
use App\Models\Importer;
use Illuminate\Support\Facades\DB;

class CreateImporterAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(ImporterData $data): Importer
    {
        return DB::transaction(function () use ($data) {
            $importer = Importer::create($data->toArray());
            $this->auditLog->log(action: 'CREATE', module: 'IMPORTER', description: "Membuat importir {$importer->name}", newValues: $importer->toArray());

            return $importer;
        });
    }
}

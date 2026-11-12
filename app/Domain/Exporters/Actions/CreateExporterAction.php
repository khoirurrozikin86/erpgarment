<?php

namespace App\Domain\Exporters\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Exporters\DTOs\ExporterData;
use App\Models\Exporter;
use Illuminate\Support\Facades\DB;

class CreateExporterAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(ExporterData $data): Exporter
    {
        return DB::transaction(function () use ($data) {
            $exporter = Exporter::create($data->toArray());
            $this->auditLog->log(action: 'CREATE', module: 'EXPORTER', description: "Membuat exportir {$exporter->name}", newValues: $exporter->toArray());

            return $exporter;
        });
    }
}

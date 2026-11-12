<?php

namespace App\Domain\Exporters\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Exporters\DTOs\ExporterData;
use App\Models\Exporter;
use Illuminate\Support\Facades\DB;

class UpdateExporterAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(Exporter $exporter, ExporterData $data): Exporter
    {
        return DB::transaction(function () use ($exporter, $data) {
            $oldValues = $exporter->getOriginal();
            $exporter->update($data->toArray());
            $exporter->refresh();
            $this->auditLog->log(action: 'UPDATE', module: 'EXPORTER', description: "Mengubah exportir {$exporter->name}", oldValues: $oldValues, newValues: $exporter->toArray());

            return $exporter;
        });
    }
}

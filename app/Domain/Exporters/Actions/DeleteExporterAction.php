<?php

namespace App\Domain\Exporters\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\Exporter;
use Illuminate\Support\Facades\DB;

class DeleteExporterAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(Exporter $exporter): bool
    {
        return DB::transaction(function () use ($exporter) {
            $oldValues = $exporter->getOriginal();
            $name = $exporter->name;
            $deleted = $exporter->delete();
            if ($deleted) {
                $this->auditLog->log(action: 'DELETE', module: 'EXPORTER', description: "Menghapus exportir {$name}", oldValues: $oldValues);
            }

            return $deleted;
        });
    }
}

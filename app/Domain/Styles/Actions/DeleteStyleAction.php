<?php

namespace App\Domain\Styles\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\Style;
use Illuminate\Support\Facades\DB;

class DeleteStyleAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(Style $style): bool
    {
        return DB::transaction(function () use ($style) {
            $oldValues = $style->toArray();
            $deleted = $style->delete();
            $this->auditLog->log(action: 'DELETE', module: 'STYLE', description: "Menghapus style {$style->code}", oldValues: $oldValues, newValues: null);
            return $deleted;
        });
    }
}

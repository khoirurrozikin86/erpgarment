<?php

namespace App\Domain\Styles\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Styles\DTOs\StyleData;
use App\Models\Style;
use Illuminate\Support\Facades\DB;

class UpdateStyleAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(Style $style, StyleData $data): Style
    {
        return DB::transaction(function () use ($style, $data) {
            $oldValues = $style->toArray();
            $style->update($data->toArray());
            $style->refresh();
            $this->auditLog->log(action: 'UPDATE', module: 'STYLE', description: "Mengubah style {$style->code}", oldValues: $oldValues, newValues: $style->toArray());
            return $style;
        });
    }
}

<?php

namespace App\Domain\Styles\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Styles\DTOs\StyleData;
use App\Models\Style;
use Illuminate\Support\Facades\DB;

class CreateStyleAction
{
    public function __construct(private AuditLogService $auditLog) {}

    public function __invoke(StyleData $data): Style
    {
        return DB::transaction(function () use ($data) {
            $style = Style::create($data->toArray());
            $this->auditLog->log(action: 'CREATE', module: 'STYLE', description: "Membuat style {$style->code}", oldValues: null, newValues: $style->toArray());
            return $style;
        });
    }
}

<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Actions\CreateAuditLogAction;
use App\Domain\Audit\DTOs\AuditLogData;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    public function __construct(
        protected CreateAuditLogAction $create,
    ) {}

    /**
     * Membuat audit log.
     */
    public function log(
        string $action,
        string $module,
        string $description,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
    ): AuditLog {
        $data = new AuditLogData(
            userId: $userId ?? Auth::id(),

            action: strtoupper($action),

            module: strtoupper($module),

            description: $description,

            model: $model,

            oldValues: $oldValues,

            newValues: $newValues,

            ipAddress: Request::ip(),

            method: Request::method(),

            url: Request::url(),

            userAgent: Request::userAgent(),
        );

        $log = ($this->create)($data);

        request()->attributes->set('audit_logged', true);

        return $log;
    }
}

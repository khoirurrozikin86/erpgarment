<?php

namespace App\Domain\Companies\Actions;

use App\Models\Company;
use App\Domain\Audit\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class DeleteCompanyAction
{
    public function __construct(
        private AuditLogService $auditLog,
    ) {}

    public function __invoke(Company $company): bool
    {
        return DB::transaction(function () use ($company) {

            $oldValues = $company->getOriginal();

            $companyName = $company->name;

            $deleted = $company->delete();

            if ($deleted) {
                $this->auditLog->log(
                    action: 'DELETE',
                    module: 'COMPANY',
                    description: "Deleted company: {$companyName}",
                    model: $company,
                    oldValues: $oldValues,
                );
            }

            return $deleted;
        });
    }
}

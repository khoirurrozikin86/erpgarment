<?php

namespace App\Domain\Companies\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\DTOs\CompanyData;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class UpdateCompanyAction
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function __invoke(
        Company $company,
        CompanyData $data
    ): Company {
        return DB::transaction(function () use ($company, $data) {

            $old = $company->getOriginal();

            $company->update(
                $data->toArray()
            );

            $company->refresh();

            $this->auditLog->log(
                action: 'UPDATE',
                module: 'COMPANY',
                description: "Mengubah company {$company->name}",
                oldValues: $old,
                newValues: $company->toArray(),
            );

            return $company;
        });
    }
}

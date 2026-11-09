<?php

namespace App\Domain\Companies\Services;

use App\Domain\Companies\Actions\CreateCompanyAction;
use App\Domain\Companies\Actions\UpdateCompanyAction;
use App\Domain\Companies\Actions\DeleteCompanyAction;
use App\Domain\Companies\DTOs\CompanyData;
use App\Models\Company;

class CompanyService
{
    public function __construct(
        protected CreateCompanyAction $create,
        protected UpdateCompanyAction $update,
        protected DeleteCompanyAction $delete,
    ) {}

    public function create(array $payload): Company
    {
        return ($this->create)(
            CompanyData::fromArray($payload)
        );
    }

    public function update(
        Company $company,
        array $payload
    ): Company {
        return ($this->update)(
            $company,
            CompanyData::fromArray($payload)
        );
    }

    public function delete(Company $company): void
    {
        ($this->delete)($company);
    }
}

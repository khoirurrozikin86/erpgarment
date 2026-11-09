<?php

namespace App\Domain\Settings\Services;

use App\Domain\Companies\Services\CompanyContext;
use App\Models\GeneralSetting;

class GeneralSettingService
{
    public function __construct(
        protected CompanyContext $companyContext
    ) {}

    public function get(): GeneralSetting
    {
        $company = $this->companyContext->get();

        return $company->generalSetting()->firstOrCreate([
            'company_id' => $company->id,
        ], [
            'decimal_places' => 2,
            'negative_stock' => false,
            'tax_included' => false,
        ]);
    }

    public function update(array $data): GeneralSetting
    {
        $setting = $this->get();

        $setting->update($data);

        return $setting->refresh();
    }
}

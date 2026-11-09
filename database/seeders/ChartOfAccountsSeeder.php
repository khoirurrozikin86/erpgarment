<?php

namespace Database\Seeders;

use App\Domain\Accounting\Services\ChartOfAccountDefaultsService;
use App\Models\Company;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(ChartOfAccountDefaultsService $defaults): void
    {
        Company::query()->each(fn (Company $company) => $defaults->seedForCompany($company));
    }
}

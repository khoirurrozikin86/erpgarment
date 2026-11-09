<?php

namespace Database\Seeders;

use App\Models\CashBankAccount;
use App\Models\ChartOfAccount;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CashBankSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->get(['id']);

        foreach ($companies as $company) {
            $cashCoaId = ChartOfAccount::query()->where('company_id', $company->id)->where('code', '1110')->value('id');
            $bankCoaId = ChartOfAccount::query()->where('company_id', $company->id)->where('code', '1120')->value('id');
            CashBankAccount::firstOrCreate(
                ['company_id' => $company->id, 'code' => 'CASH-001'],
                [
                    'name' => 'Kas Utama',
                    'type' => 'cash',
                    'chart_of_account_id' => $cashCoaId,
                    'opening_balance' => 0,
                    'is_active' => true,
                ]
            );

            CashBankAccount::firstOrCreate(
                ['company_id' => $company->id, 'code' => 'BANK-001'],
                [
                    'name' => 'Bank Utama',
                    'type' => 'bank',
                    'chart_of_account_id' => $bankCoaId,
                    'opening_balance' => 0,
                    'is_active' => true,
                ]
            );

            foreach ([['CASH-001', $cashCoaId], ['BANK-001', $bankCoaId]] as [$code, $coaId]) {
                $account = CashBankAccount::query()->where('company_id', $company->id)->where('code', $code)->first();
                if ($account && !$account->chart_of_account_id && $coaId) {
                    $account->update(['chart_of_account_id' => $coaId]);
                }
            }
        }
    }
}

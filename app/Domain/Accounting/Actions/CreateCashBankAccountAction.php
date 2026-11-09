<?php

namespace App\Domain\Accounting\Actions;

use App\Domain\Accounting\DTOs\CashBankAccountData;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\CashBankAccount;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;

class CreateCashBankAccountAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $auditLog) {}

    public function __invoke(CashBankAccountData $data): CashBankAccount
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->attributes;
            $attributes['company_id'] = $this->company->id();
            $chartAccount = ChartOfAccount::query()->where('company_id', $this->company->id())->where('account_type', 'asset')->where('is_group', false)->where('is_active', true)->find($attributes['chart_of_account_id'] ?? null);
            if (!$chartAccount) throw \Illuminate\Validation\ValidationException::withMessages(['chart_of_account_id' => 'Pilih akun COA aset aktif milik perusahaan ini.']);
            $expectedPrefix = $attributes['type'] === 'cash' ? '111' : '112';
            if (!str_starts_with($chartAccount->code, $expectedPrefix)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['chart_of_account_id' => $attributes['type'] === 'cash' ? 'Akun kas harus dipetakan ke COA kelompok 111.' : 'Akun bank harus dipetakan ke COA kelompok 112.']);
            }
            if ($attributes['type'] === 'cash') {
                $attributes['bank_name'] = $attributes['account_number'] = $attributes['account_name'] = null;
            }
            $account = CashBankAccount::create($attributes);
            $this->auditLog->log(action: 'CREATE', module: 'CASH_BANK_ACCOUNT', description: "Membuat akun kas/bank {$account->code} - {$account->name}", oldValues: null, newValues: $account->toArray());
            return $account;
        });
    }
}

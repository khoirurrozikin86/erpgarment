<?php

namespace App\Domain\Accounting\Actions;

use App\Domain\Accounting\DTOs\ChartOfAccountData;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateChartOfAccountAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $auditLog) {}

    public function __invoke(ChartOfAccountData $data): ChartOfAccount
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->attributes;
            $attributes['company_id'] = $this->company->id();
            $this->validateParent($attributes);
            $account = ChartOfAccount::create($attributes);
            $this->auditLog->log(action: 'CREATE', module: 'CHART_OF_ACCOUNTS', description: "Membuat akun {$account->code} - {$account->name}", model: $account, oldValues: null, newValues: $account->toArray());
            return $account->load('parent');
        });
    }

    private function validateParent(array $attributes): void
    {
        if (empty($attributes['parent_id'])) return;
        $parent = ChartOfAccount::query()->where('company_id', $this->company->id())->find($attributes['parent_id']);
        if (!$parent || !$parent->is_group || !$parent->is_active || $parent->account_type !== $attributes['account_type']) {
            throw ValidationException::withMessages(['parent_id' => 'Akun induk harus berupa akun grup dengan jenis akun yang sama.']);
        }
    }
}

<?php

namespace App\Domain\Accounting\Actions;

use App\Domain\Accounting\DTOs\ChartOfAccountData;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateChartOfAccountAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $auditLog) {}

    public function __invoke(ChartOfAccount $account, ChartOfAccountData $data): ChartOfAccount
    {
        return DB::transaction(function () use ($account, $data) {
            $account = ChartOfAccount::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($account->id);
            $attributes = $data->attributes;
            if ($account->is_system && ($attributes['code'] !== $account->code || $attributes['account_type'] !== $account->account_type)) {
                throw ValidationException::withMessages(['code' => 'Kode dan jenis akun standar tidak dapat diubah karena digunakan oleh posting otomatis.']);
            }
            if ((int) ($attributes['parent_id'] ?? 0) === $account->id) {
                throw ValidationException::withMessages(['parent_id' => 'Akun tidak bisa menjadi induk untuk dirinya sendiri.']);
            }
            $this->validateParent($attributes, $account);

            if (!$attributes['is_group'] && $account->children()->exists()) {
                throw ValidationException::withMessages(['is_group' => 'Akun dengan subakun harus tetap menjadi akun grup.']);
            }
            if ($account->children()->exists() && $attributes['account_type'] !== $account->account_type) {
                throw ValidationException::withMessages(['account_type' => 'Jenis akun tidak dapat diubah selama akun ini memiliki subakun.']);
            }
            $oldValues = $account->toArray();
            $account->update($attributes);
            $this->auditLog->log(action: 'UPDATE', module: 'CHART_OF_ACCOUNTS', description: "Memperbarui akun {$account->code} - {$account->name}", model: $account, oldValues: $oldValues, newValues: $account->fresh()->toArray());
            return $account->fresh('parent');
        });
    }

    private function validateParent(array $attributes, ChartOfAccount $account): void
    {
        if (empty($attributes['parent_id'])) return;
        $parent = ChartOfAccount::query()->where('company_id', $this->company->id())->find($attributes['parent_id']);
        if (!$parent || !$parent->is_group || !$parent->is_active || $parent->account_type !== $attributes['account_type']) {
            throw ValidationException::withMessages(['parent_id' => 'Akun induk harus berupa akun grup dengan jenis akun yang sama.']);
        }
        $ancestor = $parent;
        while ($ancestor) {
            if ($ancestor->id === $account->id) {
                throw ValidationException::withMessages(['parent_id' => 'Struktur akun tidak boleh membentuk siklus.']);
            }
            $ancestor = $ancestor->parent;
        }
    }
}

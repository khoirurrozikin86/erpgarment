<?php

namespace App\Domain\Subcontract\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{SubcontractContract, Subcontractor};
use Illuminate\Support\Facades\DB;

class SaveSubcontractContractAction
{
    public function create(array $data, int $companyId, int $userId): SubcontractContract
    {
        return DB::transaction(function () use ($data, $companyId, $userId) {
            $number = app(DocumentNumberService::class)->generate('subcontract_contract');
            $record = SubcontractContract::create($data + ['company_id' => $companyId, 'contract_number' => $number, 'created_by' => $userId]);
            app(AuditLogService::class)->log('CREATE', 'SUBCONTRACT_CONTRACT', "Created subcontract contract {$number}", $record, null, $record->only(['subcontractor_id','contract_number','bc_decree_number','description','starts_on','ends_on','quota']), $userId);
            return $record;
        });
    }

    public function update(SubcontractContract $record, array $data, int $userId): SubcontractContract
    {
        return DB::transaction(function () use ($record, $data, $userId) {
            $old = $record->only(['subcontractor_id','contract_number','bc_decree_number','description','starts_on','ends_on','quota']); $record->update($data);
            app(AuditLogService::class)->log('UPDATE', 'SUBCONTRACT_CONTRACT', "Updated subcontract contract {$record->contract_number}", $record, $old, $record->only(array_keys($old)), $userId);
            return $record;
        });
    }

    public function delete(SubcontractContract $record, int $userId): void
    {
        DB::transaction(function () use ($record, $userId) {
            $old = $record->only(['subcontractor_id','contract_number','bc_decree_number','description','starts_on','ends_on','quota']); $number = $record->contract_number; $record->delete();
            app(AuditLogService::class)->log('DELETE', 'SUBCONTRACT_CONTRACT', "Deleted subcontract contract {$number}", $record, $old, null, $userId);
        });
    }

    public function subcontractorBelongsToCompany(int $id, int $companyId): bool
    {
        return Subcontractor::query()->whereKey($id)->where('company_id', $companyId)->where('is_active', true)->exists();
    }
}

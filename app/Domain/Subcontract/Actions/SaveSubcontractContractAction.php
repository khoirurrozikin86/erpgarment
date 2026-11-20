<?php

namespace App\Domain\Subcontract\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{Company, Customer, SubcontractContract, Subcontractor};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveSubcontractContractAction
{
    public function create(array $data, int $companyId, int $userId): SubcontractContract
    {
        return DB::transaction(function () use ($data, $companyId, $userId) {
            $data = $this->resolveParty($data, $companyId);
            $number = app(DocumentNumberService::class)->generate('subcontract_contract');
            $record = SubcontractContract::create($data + ['company_id' => $companyId, 'contract_number' => $number, 'created_by' => $userId]);
            app(AuditLogService::class)->log('CREATE', 'SUBCONTRACT_CONTRACT', "Created subcontract contract {$number}", $record, null, $record->only(['subcontractor_id','customer_id','contract_number','bc_decree_number','description','starts_on','ends_on','quota']), $userId);
            return $record;
        });
    }

    public function update(SubcontractContract $record, array $data, int $userId): SubcontractContract
    {
        return DB::transaction(function () use ($record, $data, $userId) {
            $data = $this->resolveParty($data, (int) $record->company_id, $record);
            $old = $record->only(['subcontractor_id','customer_id','contract_number','bc_decree_number','description','starts_on','ends_on','quota']); $record->update($data);
            app(AuditLogService::class)->log('UPDATE', 'SUBCONTRACT_CONTRACT', "Updated subcontract contract {$record->contract_number}", $record, $old, $record->only(array_keys($old)), $userId);
            return $record;
        });
    }

    private function resolveParty(array $data, int $companyId, ?SubcontractContract $contract = null): array
    {
        $customerId = $data['customer_id'] ?? null;
        if (!$customerId) {
            unset($data['customer_id']);
            if (isset($data['subcontractor_id'])) {
                if ($contract && (int) $data['subcontractor_id'] !== (int) $contract->subcontractor_id) {
                    $data['customer_id'] = null;
                }
            } elseif ($contract) {
                $data['subcontractor_id'] = $contract->subcontractor_id;
            }

            return $data;
        }

        $customer = Customer::query()->where('is_active', true)->find($customerId);
        if (!$customer) {
            throw ValidationException::withMessages(['customer_id' => __('ui.subcontract.invalid_subcontractor')]);
        }

        // Serialize creation of the company-scoped party used by existing Subkon transactions.
        Company::query()->lockForUpdate()->findOrFail($companyId);
        $relatedContract = SubcontractContract::query()->where('company_id', $companyId)
            ->where('customer_id', $customer->id)->withTrashed()->latest('id')->first();
        $partyId = (int) $contract?->customer_id === (int) $customer->id
            ? $contract->subcontractor_id : $relatedContract?->subcontractor_id;
        $parties = Subcontractor::withTrashed()->where('company_id', $companyId);
        $party = $partyId
            ? (clone $parties)->find($partyId)
            : null;
        $party ??= $parties->firstOrNew(['company_id' => $companyId, 'name' => $customer->name]);
        $party->forceFill(['is_active' => true, 'deleted_at' => null])->save();

        return [...$data, 'subcontractor_id' => $party->id];
    }

    public function delete(SubcontractContract $record, int $userId): void
    {
        DB::transaction(function () use ($record, $userId) {
            $old = $record->only(['subcontractor_id','customer_id','contract_number','bc_decree_number','description','starts_on','ends_on','quota']); $number = $record->contract_number; $record->delete();
            app(AuditLogService::class)->log('DELETE', 'SUBCONTRACT_CONTRACT', "Deleted subcontract contract {$number}", $record, $old, null, $userId);
        });
    }

    public function subcontractorBelongsToCompany(int $id, int $companyId): bool
    {
        return Subcontractor::query()->whereKey($id)->where('company_id', $companyId)->where('is_active', true)->exists();
    }
}

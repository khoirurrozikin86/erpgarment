<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{EquipmentIssueDocument, Product};
use Illuminate\Support\Facades\DB;

class SaveEquipmentIssueAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $audit, private DocumentNumberService $numbers) {}

    public function create(array $data, int $userId): EquipmentIssueDocument
    {
        return DB::transaction(function () use ($data, $userId) {
            $doc = EquipmentIssueDocument::query()->create(['company_id' => $this->company->id(), 'number' => $this->numbers->generate('equipment_issue'), 'issue_date' => $data['issue_date'], 'created_by' => $userId]);
            $this->saveItem($doc, $data);
            $this->audit->log('CREATE', 'EQUIPMENT_ISSUES', "Mencatat pengeluaran mesin {$doc->number}", $doc, null, $doc->load('item')->toArray(), $userId);
            return $doc->load('item');
        });
    }

    public function update(EquipmentIssueDocument $issue, array $data, int $userId): EquipmentIssueDocument
    {
        return DB::transaction(function () use ($issue, $data, $userId) {
            $doc = EquipmentIssueDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($issue->id);
            $old = $doc->load('item')->toArray();
            $doc->update(['issue_date' => $data['issue_date']]);
            $doc->item()->delete();
            $this->saveItem($doc, $data);
            $this->audit->log('UPDATE', 'EQUIPMENT_ISSUES', "Memperbarui pengeluaran mesin {$doc->number}", $doc, $old, $doc->fresh('item')->toArray(), $userId);
            return $doc->load('item');
        });
    }

    public function delete(EquipmentIssueDocument $issue, int $userId): void
    {
        DB::transaction(function () use ($issue, $userId) {
            $doc = EquipmentIssueDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($issue->id);
            $old = $doc->load('item')->toArray();
            $doc->delete();
            $this->audit->log('DELETE', 'EQUIPMENT_ISSUES', "Menghapus pengeluaran mesin {$doc->number}", $doc, $old, null, $userId);
        });
    }

    private function saveItem(EquipmentIssueDocument $doc, array $data): void
    {
        $product = Product::query()->where('is_active', true)->whereHas('category', fn ($q) => $q->whereIn('code', ['MACHINE', 'EQUIPMENT']))->findOrFail($data['product_id']);
        $doc->item()->create(['product_id' => $product->id, 'item_code' => $product->code, 'item_name' => $product->name, 'unit' => $product->unit?->symbol ?: $product->unit?->name ?: '', 'quantity' => $data['quantity'], 'description' => $data['description'] ?? null]);
    }
}

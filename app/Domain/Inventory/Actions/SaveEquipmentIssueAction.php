<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Queries\EquipmentStockCardQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{Company, EquipmentIssueDocument, Product};
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveEquipmentIssueAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $audit, private DocumentNumberService $numbers, private EquipmentStockCardQuery $stock) {}

    public function create(array $data, int $userId): EquipmentIssueDocument
    {
        return DB::transaction(function () use ($data, $userId) {
            Company::query()->lockForUpdate()->findOrFail($this->company->id());

            $doc = EquipmentIssueDocument::query()->create(['company_id' => $this->company->id(), 'number' => $this->numbers->generate('equipment_issue'), 'issue_date' => $data['issue_date'], 'created_by' => $userId]);
            $this->saveItem($doc, $data);
            $this->audit->log('CREATE', 'EQUIPMENT_ISSUES', "Mencatat pengeluaran mesin {$doc->number}", $doc, null, $doc->load('item')->toArray(), $userId);
            return $doc->load('item');
        });
    }

    public function update(EquipmentIssueDocument $issue, array $data, int $userId): EquipmentIssueDocument
    {
        return DB::transaction(function () use ($issue, $data, $userId) {
            Company::query()->lockForUpdate()->findOrFail($this->company->id());

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
            Company::query()->lockForUpdate()->findOrFail($this->company->id());

            $doc = EquipmentIssueDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($issue->id);
            $old = $doc->load('item')->toArray();
            $doc->delete();
            $this->audit->log('DELETE', 'EQUIPMENT_ISSUES', "Menghapus pengeluaran mesin {$doc->number}", $doc, $old, null, $userId);
        });
    }

    private function saveItem(EquipmentIssueDocument $doc, array $data): void
    {
        $product = Product::query()->where('is_active', true)->whereHas('category', fn ($q) => $q->whereIn('code', ['MACHINE', 'EQUIPMENT']))->findOrFail($data['product_id']);
        $balance = $this->stock->balance($product->id, $doc->company_id);
        $quantity = BigDecimal::of((string) $data['quantity'])->toScale(4, RoundingMode::HALF_UP);

        if ($balance->isLessThanOrEqualTo(0) || $quantity->isGreaterThan($balance)) {
            throw ValidationException::withMessages([
                'quantity' => __('ui.machine_issue.insufficient_stock', [
                    'code' => $product->code,
                    'available' => \App\Support\DisplayNumber::format(max(0, $balance->toFloat()), 4),
                ]),
            ]);
        }

        $doc->item()->create(['product_id' => $product->id, 'item_code' => $product->code, 'item_name' => $product->name, 'unit' => $product->unit?->symbol ?: $product->unit?->name ?: '', 'quantity' => $data['quantity'], 'description' => $data['description'] ?? null]);
    }
}

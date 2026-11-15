<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{EquipmentReceiptDocument, Product, Supplier};
use Illuminate\Support\Facades\DB;

class SaveEquipmentReceiptAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $audit, private DocumentNumberService $numbers) {}

    public function create(array $data, int $userId): EquipmentReceiptDocument
    {
        return DB::transaction(function () use ($data, $userId) {
            $document = EquipmentReceiptDocument::query()->create([
                ...$this->header($data), 'company_id' => $this->company->id(),
                'number' => $this->numbers->generate('equipment_receipt'), 'created_by' => $userId,
            ]);
            $this->saveItems($document, $data['items']);
            $this->audit->log('CREATE', 'EQUIPMENT_RECEIPTS', "Mencatat penerimaan mesin {$document->number}", $document, null, $document->load('items')->toArray(), $userId);
            return $document->load(['items', 'supplier']);
        });
    }

    public function update(EquipmentReceiptDocument $receipt, array $data, int $userId): EquipmentReceiptDocument
    {
        return DB::transaction(function () use ($receipt, $data, $userId) {
            $document = EquipmentReceiptDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($receipt->id);
            $old = $document->load('items')->toArray();
            $document->update($this->header($data));
            $document->items()->delete();
            $this->saveItems($document, $data['items']);
            $this->audit->log('UPDATE', 'EQUIPMENT_RECEIPTS', "Memperbarui penerimaan mesin {$document->number}", $document, $old, $document->fresh('items')->toArray(), $userId);
            return $document->load(['items', 'supplier']);
        });
    }

    public function delete(EquipmentReceiptDocument $receipt, int $userId): void
    {
        DB::transaction(function () use ($receipt, $userId) {
            $document = EquipmentReceiptDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($receipt->id);
            $old = $document->load('items')->toArray();
            $document->delete();
            $this->audit->log('DELETE', 'EQUIPMENT_RECEIPTS', "Menghapus penerimaan mesin {$document->number}", $document, $old, null, $userId);
        });
    }

    private function header(array $data): array
    {
        if (!empty($data['supplier_id'])) Supplier::query()->where('is_active', true)->findOrFail($data['supplier_id']);
        return ['receipt_date' => $data['receipt_date'], 'ownership_type' => $data['ownership_type'], 'invoice_number' => $data['invoice_number'] ?? null, 'invoice_date' => $data['invoice_date'] ?? null, 'supplier_id' => $data['supplier_id'] ?? null, 'currency_code' => $data['currency_code'] ?? null, 'description' => $data['description'] ?? null];
    }

    private function saveItems(EquipmentReceiptDocument $document, array $items): void
    {
        foreach ($items as $line) {
            $product = Product::query()->where('is_active', true)->whereHas('category', fn ($q) => $q->whereIn('code', ['MACHINE', 'EQUIPMENT']))->findOrFail($line['product_id']);
            $qty = (float) $line['quantity']; $price = (float) ($line['unit_price'] ?? 0);
            $document->items()->create(['product_id' => $product->id, 'item_code' => $product->code, 'item_name' => $product->name, 'unit' => $product->unit?->symbol ?: $product->unit?->name ?: '', 'quantity' => $qty, 'unit_price' => $price, 'total_price' => $qty * $price, 'supplier_po_number' => $line['supplier_po_number'] ?? null, 'description' => $line['description'] ?? null]);
        }
    }
}

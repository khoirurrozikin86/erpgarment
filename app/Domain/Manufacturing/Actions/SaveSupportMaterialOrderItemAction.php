<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{Product, SupportMaterialOrderDocument, SupportMaterialOrderItem};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveSupportMaterialOrderItemAction
{
    public function __construct(private CompanyContext $company, private DocumentNumberService $numbers, private AuditLogService $audit) {}

    public function create(array $data, int $userId): SupportMaterialOrderItem
    {
        return DB::transaction(function () use ($data, $userId) {
            $product = Product::query()->where('is_active', true)->findOrFail($data['product_id']);
            $documentId = $data['document_id'] ?? null;
            if ($documentId) {
                $document = SupportMaterialOrderDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($documentId);
                $document->update(['order_date' => $data['order_date']]);
            } else {
                $document = SupportMaterialOrderDocument::query()->create([
                    'company_id' => $this->company->id(), 'number' => $this->numbers->generate('support_material_order'),
                    'order_date' => $data['order_date'], 'created_by' => $userId,
                ]);
            }
            $item = $document->items()->create(['product_id' => $product->id, 'quantity' => $data['quantity'], 'po_other' => $data['po_other'] ?? null, 'style' => $data['style'] ?? null]);
            $this->audit->log('CREATE', 'SUPPORT_MATERIAL_ORDER', "Mencatat order support material {$document->number}", $item, null, $item->load(['document', 'product'])->toArray(), $userId);
            return $item->load(['document', 'product.unit']);
        });
    }

    public function update(SupportMaterialOrderItem $item, array $data, int $userId): SupportMaterialOrderItem
    {
        return DB::transaction(function () use ($item, $data, $userId) {
            $locked = SupportMaterialOrderItem::query()->lockForUpdate()->findOrFail($item->id);
            $document = SupportMaterialOrderDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($locked->document_id);
            $old = $locked->load(['document', 'product'])->toArray();
            $product = Product::query()->where('is_active', true)->findOrFail($data['product_id']);
            $document->update(['order_date' => $data['order_date']]);
            $locked->update(['product_id' => $product->id, 'quantity' => $data['quantity'], 'po_other' => $data['po_other'] ?? null, 'style' => $data['style'] ?? null]);
            $this->audit->log('UPDATE', 'SUPPORT_MATERIAL_ORDER', "Memperbarui order support material {$document->number}", $locked, $old, $locked->fresh(['document', 'product'])->toArray(), $userId);
            return $locked->fresh(['document', 'product.unit']);
        });
    }

    public function delete(SupportMaterialOrderItem $item, int $userId): bool
    {
        return DB::transaction(function () use ($item, $userId) {
            $locked = SupportMaterialOrderItem::query()->lockForUpdate()->findOrFail($item->id);
            $document = SupportMaterialOrderDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($locked->document_id);
            $old = $locked->load(['document', 'product'])->toArray();
            $locked->delete();
            $empty = !$document->items()->exists();
            if ($empty) $document->delete();
            $this->audit->log('DELETE', 'SUPPORT_MATERIAL_ORDER', "Menghapus rincian order support material {$document->number}", $locked, $old, ['deleted_at' => now()->toDateTimeString()], $userId);
            return $empty;
        });
    }
}

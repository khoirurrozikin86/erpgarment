<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\DTOs\RawMaterialOrderData;
use App\Models\RawMaterialOrderDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateRawMaterialOrderAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $audit) {}

    public function __invoke(RawMaterialOrderDocument $document, RawMaterialOrderData $data, int $userId): RawMaterialOrderDocument
    {
        return DB::transaction(function () use ($document, $data, $userId) {
            $document = RawMaterialOrderDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($document->id);
            if ($document->production_order_number !== $data->productionOrderNumber) {
                throw ValidationException::withMessages(['production_order_number' => __('ui.raw_material_order.source_mo_locked')]);
            }
            $old = $document->load('items')->toArray();
            $items = $document->items()->lockForUpdate()->get()->keyBy('production_order_material_id');
            if ($document->items()->where('issued_quantity', '>', 0)->exists()) {
                throw ValidationException::withMessages(['items' => __('ui.raw_material_order.issued_locked')]);
            }
            $submitted = collect($data->items)->keyBy(fn ($item) => (int) $item['production_order_material_id']);
            if ($submitted->count() !== $items->count() || $submitted->keys()->sort()->values()->all() !== $items->keys()->map(fn ($id) => (int) $id)->sort()->values()->all()) {
                throw ValidationException::withMessages(['items' => __('ui.raw_material_order.mo_items_changed')]);
            }
            $document->update(['order_date' => $data->orderDate]);
            foreach ($items as $sourceId => $item) $item->update(['quantity' => $submitted->get($sourceId)['quantity']]);
            $this->audit->log('UPDATE', 'RAW_MATERIAL_ORDERS', "Memperbarui Order Bahan Baku {$document->number}", $document, $old, $document->fresh('items')->toArray(), $userId);
            return $document->fresh(['items.product.unit']);
        });
    }
}

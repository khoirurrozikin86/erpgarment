<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\DTOs\RawMaterialOrderData;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{Product, RawMaterialOrderDocument};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateRawMaterialOrderAction
{
    public function __construct(private CompanyContext $company, private DocumentNumberService $numbers, private AuditLogService $audit) {}

    public function __invoke(RawMaterialOrderDocument $document, RawMaterialOrderData $data, int $userId): RawMaterialOrderDocument
    {
        return DB::transaction(function () use ($document, $data, $userId) {
            $document = RawMaterialOrderDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($document->id);
            $isManual = $document->production_order_number === null || $data->mode === 'manual';
            $old = $document->load('items')->toArray();

            if ($document->items()->where('issued_quantity', '>', 0)->exists()) {
                throw ValidationException::withMessages(['items' => __('ui.raw_material_order.issued_locked')]);
            }

            if ($isManual) {
                if (empty($data->items)) {
                    throw ValidationException::withMessages(['items' => __('ui.raw_material_order.empty_materials')]);
                }
                $document->update(['order_date' => $data->orderDate, 'production_order_number' => null]);
                $document->items()->delete();

                foreach (collect($data->items)->values() as $index => $item) {
                    $product = Product::query()->whereKey((int) $item['product_id'])->lockForUpdate()->first();
                    if (!$product || !$product->is_active) {
                        throw ValidationException::withMessages(["items.$index.product_id" => 'Barang manual tidak ditemukan atau tidak aktif.']);
                    }
                    if (!$product->track_stock) {
                        throw ValidationException::withMessages(["items.$index.product_id" => 'Barang manual harus merupakan barang yang melacak stok.']);
                    }
                    $document->items()->create([
                        'company_id' => $this->company->id(),
                        'number' => $index === 0 ? $document->number : $this->numbers->generate('raw_material_order'),
                        'production_order_material_id' => null,
                        'product_id' => $product->id,
                        'style_code' => trim((string) ($item['style_code'] ?? $product->style_code)) ?: null,
                        'color' => trim((string) ($item['color'] ?? '')) ?: null,
                        'size' => trim((string) ($item['size'] ?? '')) ?: null,
                        'category' => trim((string) ($item['category'] ?? 'general')) ?: 'general',
                        'quantity' => (float) $item['quantity'],
                    ]);
                }

                $this->audit->log('UPDATE', 'RAW_MATERIAL_ORDERS', "Memperbarui Order Bahan Baku Manual {$document->number}", $document, $old, $document->fresh('items')->toArray(), $userId);
                return $document->fresh(['items.product.unit']);
            }

            if ($document->production_order_number !== $data->productionOrderNumber) {
                throw ValidationException::withMessages(['production_order_number' => __('ui.raw_material_order.source_mo_locked')]);
            }
            $old = $document->load('items')->toArray();
            $items = $document->items()->lockForUpdate()->get()->keyBy('production_order_material_id');
            if ($document->items()->where('issued_quantity', '>', 0)->exists()) {
                throw ValidationException::withMessages(['items' => __('ui.raw_material_order.issued_locked')]);
            }
            $submitted = collect($data->items)->keyBy(fn ($item) => (int) $item['production_order_material_id']);
            if ($submitted->isEmpty()) {
                throw ValidationException::withMessages(['items' => __('ui.raw_material_order.mo_items_changed')]);
            }
            if ($submitted->keys()->contains(fn ($id) => !$items->has((int) $id))) {
                throw ValidationException::withMessages(['items' => __('ui.raw_material_order.mo_items_changed')]);
            }
            $document->update(['order_date' => $data->orderDate]);
            foreach ($items as $sourceId => $item) {
                if (!$submitted->has((int) $sourceId)) {
                    $item->delete();
                    continue;
                }
                $submittedItem = $submitted->get((int) $sourceId);
                $item->update([
                    'quantity' => $submittedItem['quantity'],
                    'category' => $submittedItem['category'] ?? $item->category,
                ]);
            }
            $this->audit->log('UPDATE', 'RAW_MATERIAL_ORDERS', "Memperbarui Order Bahan Baku {$document->number}", $document, $old, $document->fresh('items')->toArray(), $userId);
            return $document->fresh(['items.product.unit']);
        });
    }
}

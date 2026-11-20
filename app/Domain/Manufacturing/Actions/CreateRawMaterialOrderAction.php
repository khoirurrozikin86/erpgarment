<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\DTOs\RawMaterialOrderData;
use App\Domain\Manufacturing\Queries\RawMaterialOrderTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{Product, ProductionOrderMaterial, RawMaterialOrderDocument};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateRawMaterialOrderAction
{
    public function __construct(private CompanyContext $company, private DocumentNumberService $numbers, private AuditLogService $audit, private RawMaterialOrderTableQuery $query) {}

    public function __invoke(RawMaterialOrderData $data, int $userId): RawMaterialOrderDocument
    {
        return DB::transaction(function () use ($data, $userId) {
            if ($data->mode === 'manual') {
                if (empty($data->items)) {
                    throw ValidationException::withMessages(['items' => __('ui.raw_material_order.empty_materials')]);
                }

                $document = RawMaterialOrderDocument::query()->create([
                    'company_id' => $this->company->id(),
                    'number' => $this->numbers->generate('raw_material_order'),
                    'order_date' => $data->orderDate,
                    'production_order_number' => null,
                    'created_by' => $userId,
                ]);

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

                $this->audit->log('CREATE', 'RAW_MATERIAL_ORDERS', "Membuat Order Bahan Baku Manual {$document->number}", $document, null, $document->load('items.product')->toArray(), $userId);
                return $document->load('items.product.unit');
            }

            if (!$data->productionOrderNumber) {
                throw ValidationException::withMessages(['production_order_number' => __('ui.raw_material_order.select_mo_first')]);
            }
            $materials = $this->query->materialsForMo($this->company->id(), $data->productionOrderNumber, $data->category);
            $submittedIds = collect($data->items)->pluck('production_order_material_id')->map(fn ($id) => (int) $id)->sort()->values();
            $availableIds = $materials->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
            if ($materials->isEmpty() || $submittedIds->isEmpty() || $submittedIds->unique()->count() !== $submittedIds->count()) {
                throw ValidationException::withMessages(['items' => __('ui.raw_material_order.mo_items_changed')]);
            }
            if ($submittedIds->contains(fn ($id) => !$availableIds->contains($id))) {
                throw ValidationException::withMessages(['items' => __('ui.raw_material_order.mo_items_changed')]);
            }

            $quantities = collect($data->items)->keyBy(fn ($item) => (int) $item['production_order_material_id']);
            $document = RawMaterialOrderDocument::query()->create([
                'company_id' => $this->company->id(), 'number' => $this->numbers->generate('raw_material_order'),
                'order_date' => $data->orderDate, 'production_order_number' => $data->productionOrderNumber, 'created_by' => $userId,
            ]);
            foreach ($materials->whereIn('id', $submittedIds->all())->values() as $index => $source) {
                $document->items()->create([
                    'company_id' => $this->company->id(),
                    'number' => $index === 0 ? $document->number : $this->numbers->generate('raw_material_order'),
                    'production_order_material_id' => $source->id,
                    'product_id' => $source->product_id,
                    'style_code' => $source->productionOrder?->model_style,
                    'category' => $source->billOfMaterial?->bom_type ?? $source->productionOrder?->billOfMaterial?->bom_type,
                    'quantity' => $quantities->get($source->id)['quantity'],
                ]);
            }
            $this->audit->log('CREATE', 'RAW_MATERIAL_ORDERS', "Membuat Order Bahan Baku {$document->number} dari MO {$document->production_order_number}", $document, null, $document->load('items.product')->toArray(), $userId);
            return $document->load('items.product.unit');
        });
    }
}

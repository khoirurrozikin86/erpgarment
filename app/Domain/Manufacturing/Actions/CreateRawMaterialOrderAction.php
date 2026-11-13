<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\DTOs\RawMaterialOrderData;
use App\Domain\Manufacturing\Queries\RawMaterialOrderTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{ProductionOrderMaterial, RawMaterialOrderDocument};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateRawMaterialOrderAction
{
    public function __construct(private CompanyContext $company, private DocumentNumberService $numbers, private AuditLogService $audit, private RawMaterialOrderTableQuery $query) {}

    public function __invoke(RawMaterialOrderData $data, int $userId): RawMaterialOrderDocument
    {
        return DB::transaction(function () use ($data, $userId) {
            $materials = $this->query->materialsForMo($this->company->id(), $data->productionOrderNumber);
            $submittedIds = collect($data->items)->pluck('production_order_material_id')->map(fn ($id) => (int) $id)->sort()->values();
            if ($materials->isEmpty() || $submittedIds->count() !== $materials->count() || $submittedIds->unique()->count() !== $materials->count() || $submittedIds->all() !== $materials->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all()) {
                throw ValidationException::withMessages(['items' => __('ui.raw_material_order.mo_items_changed')]);
            }

            $quantities = collect($data->items)->keyBy(fn ($item) => (int) $item['production_order_material_id']);
            $document = RawMaterialOrderDocument::query()->create([
                'company_id' => $this->company->id(), 'number' => $this->numbers->generate('raw_material_order'),
                'order_date' => $data->orderDate, 'production_order_number' => $data->productionOrderNumber, 'created_by' => $userId,
            ]);
            foreach ($materials->values() as $index => $source) {
                $document->items()->create([
                    'company_id' => $this->company->id(),
                    'number' => $index === 0 ? $document->number : $this->numbers->generate('raw_material_order'),
                    'production_order_material_id' => $source->id,
                    'product_id' => $source->product_id,
                    'style_code' => $source->productionOrder?->outputProduct?->style_code,
                    'quantity' => $quantities->get($source->id)['quantity'],
                ]);
            }
            $this->audit->log('CREATE', 'RAW_MATERIAL_ORDERS', "Membuat Order Bahan Baku {$document->number} dari MO {$document->production_order_number}", $document, null, $document->load('items.product')->toArray(), $userId);
            return $document->load('items.product.unit');
        });
    }
}

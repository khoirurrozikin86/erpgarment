<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{Product, ProductionReturnDocument};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateProductionReturnAction
{
    public function __construct(private CompanyContext $company, private DocumentNumberService $numbers, private AuditLogService $audit) {}

    public function __invoke(array $data, int $userId): ProductionReturnDocument
    {
        return DB::transaction(function () use ($data, $userId) {
            $product = Product::query()->where('track_stock', true)->where('is_active', true)->findOrFail($data['product_id']);
            $orderId = $data['raw_material_order_document_id'] ?? null;
            if ($orderId) {
                $order = \App\Models\RawMaterialOrderDocument::query()->where('company_id', $this->company->id())->findOrFail($orderId);
                $hasItem = $order->items()->where('product_id', $product->id)->exists();
                if (!$hasItem) throw ValidationException::withMessages(['product_id' => __('ui.production_return.item_not_in_order')]);
            }
            $document = ProductionReturnDocument::query()->create([
                'company_id' => $this->company->id(), 'number' => $this->numbers->generate('return_to_warehouse'),
                'return_date' => $data['return_date'], 'raw_material_order_document_id' => $orderId,
                'po_supplier_number' => $data['po_supplier_number'] ?? null, 'created_by' => $userId,
            ]);
            $document->items()->create(['product_id' => $product->id, 'quantity' => $data['quantity'], 'description' => $data['description'] ?? null]);
            $this->audit->log('CREATE', 'RETURN_TO_WAREHOUSE', "Mencatat retur produksi {$document->number}", $document, null, $document->load('items')->toArray(), $userId);
            return $document->load(['items.product.unit', 'rawMaterialOrder']);
        });
    }
}

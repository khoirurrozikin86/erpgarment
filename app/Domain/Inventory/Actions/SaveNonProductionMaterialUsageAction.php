<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{NonProductionMaterialUsageDocument, NonProductionMaterialUsageItem, Product, ProductStock, StockMovement};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveNonProductionMaterialUsageAction
{
    public function __construct(
        private CompanyContext $company,
        private AuditLogService $audit,
        private AutomaticJournalService $journals,
        private DocumentNumberService $numbers,
    ) {}

    public function create(array $data, int $userId): NonProductionMaterialUsageItem
    {
        return DB::transaction(function () use ($data, $userId) {
            $product = $this->product((int) $data['product_id']);
            $document = NonProductionMaterialUsageDocument::query()->create([
                'company_id' => $this->company->id(),
                'number' => $this->numbers->generate('non_production_material_usage'),
                'transaction_date' => $data['transaction_date'],
                'created_by' => $userId,
            ]);
            $item = $document->items()->create([
                'product_id' => $product->id,
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'total_price' => round((float) $data['quantity'] * (float) $data['unit_price'], 4),
            ]);
            $this->postMovement($document, $item, $product, -(float) $item->quantity, (float) $item->unit_price, $userId);
            $this->audit->log('CREATE', 'NON_PRODUCTION_MATERIAL_USAGE', "Mencatat pemakaian non-produksi {$product->code} pada {$document->number}", $item, null, $item->load(['document', 'product'])->toArray(), $userId);
            return $item->load(['document', 'product.unit']);
        });
    }

    public function update(NonProductionMaterialUsageItem $usageItem, array $data, int $userId): NonProductionMaterialUsageItem
    {
        return DB::transaction(function () use ($usageItem, $data, $userId) {
            $item = NonProductionMaterialUsageItem::query()->lockForUpdate()->findOrFail($usageItem->id);
            $document = NonProductionMaterialUsageDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($item->document_id);
            $old = $item->load(['document', 'product'])->toArray();
            $oldProduct = Product::query()->where('track_stock', true)->lockForUpdate()->findOrFail($item->product_id);
            $newProduct = $this->product((int) $data['product_id']);

            $this->postMovement($document, $item, $oldProduct, (float) $item->quantity, (float) $item->unit_price, $userId, 'non_production_material_usage_reversal');
            $document->update(['transaction_date' => $data['transaction_date']]);
            $item->update([
                'product_id' => $newProduct->id,
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'total_price' => round((float) $data['quantity'] * (float) $data['unit_price'], 4),
            ]);
            $this->postMovement($document, $item, $newProduct, -(float) $item->quantity, (float) $item->unit_price, $userId);
            $this->audit->log('UPDATE', 'NON_PRODUCTION_MATERIAL_USAGE', "Memperbarui pemakaian {$document->number}", $item, $old, $item->fresh(['document', 'product'])->toArray(), $userId);
            return $item->fresh(['document', 'product.unit']);
        });
    }

    public function delete(NonProductionMaterialUsageItem $usageItem, int $userId): void
    {
        DB::transaction(function () use ($usageItem, $userId) {
            $item = NonProductionMaterialUsageItem::query()->lockForUpdate()->findOrFail($usageItem->id);
            $document = NonProductionMaterialUsageDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($item->document_id);
            $item->load('product');
            $old = $item->load('document')->toArray();
            $product = Product::query()->where('track_stock', true)->lockForUpdate()->findOrFail($item->product_id);
            $this->postMovement($document, $item, $product, (float) $item->quantity, (float) $item->unit_price, $userId, 'non_production_material_usage_reversal');
            $item->delete();
            $document->delete();
            $this->audit->log('DELETE', 'NON_PRODUCTION_MATERIAL_USAGE', "Membatalkan pemakaian {$document->number}", $item, $old, ['deleted_at' => now()->toDateTimeString()], $userId);
        });
    }

    private function product(int $id): Product
    {
        return Product::query()->where('track_stock', true)->where('is_active', true)->lockForUpdate()->findOrFail($id);
    }

    private function postMovement(NonProductionMaterialUsageDocument $document, NonProductionMaterialUsageItem $item, Product $product, float $quantity, float $unitPrice, int $userId, string $type = 'non_production_material_usage'): void
    {
        if (abs($quantity) < 0.0000001) return;
        $stock = ProductStock::query()->firstOrCreate(['product_id' => $product->id], ['quantity' => 0, 'average_unit_cost' => 0]);
        $stock = ProductStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
        $before = round((float) $stock->quantity, 4);
        $after = round($before + $quantity, 4);
        if ($after < 0) throw ValidationException::withMessages(['quantity' => __('ui.non_production_usage.insufficient_stock', ['item' => $product->name])]);
        $stock->update(['quantity' => $after]);
        $movement = StockMovement::query()->create([
            'product_id' => $product->id,
            'non_production_material_usage_document_id' => $document->id,
            'non_production_material_usage_item_id' => $item->id,
            'created_by' => $userId,
            'movement_type' => $type,
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'cost_amount' => round(abs($quantity) * $unitPrice, 2),
            'notes' => __('ui.non_production_usage.stock_reference', ['number' => $document->number]),
        ]);
        $this->journals->recordStockMovement($movement, $userId);
    }
}

<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{Product, ProductStock, ScrapReceiptDocument, StockMovement};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveScrapReceiptAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $audit, private DocumentNumberService $numbers) {}

    public function create(array $data, int $userId): ScrapReceiptDocument
    {
        return DB::transaction(function () use ($data, $userId) {
            $scrap = $this->scrapProduct((int) $data['scrap_product_id']);
            $source = Product::query()->where('track_stock', true)->where('is_active', true)->lockForUpdate()->findOrFail($data['source_product_id']);
            $document = ScrapReceiptDocument::query()->create([
                'company_id' => $this->company->id(), 'number' => $this->numbers->generate('scrap_receipt'),
                'receipt_date' => $data['receipt_date'], 'scrap_product_id' => $scrap->id,
                'source_product_id' => $source->id, 'production_number' => $data['production_number'] ?? null,
                'quantity' => $data['quantity'], 'description' => $data['description'] ?? null, 'created_by' => $userId,
            ]);
            $this->move($document, $scrap, (float) $document->quantity, $userId, 'scrap_receipt');
            $this->audit->log('CREATE', 'SCRAP_RECEIPTS', "Mencatat penerimaan scrap {$document->number}", $document, null, $document->load(['scrapProduct', 'sourceProduct'])->toArray(), $userId);
            return $document->load(['scrapProduct.unit', 'sourceProduct.unit', 'creator']);
        });
    }

    public function update(ScrapReceiptDocument $scrapReceipt, array $data, int $userId): ScrapReceiptDocument
    {
        return DB::transaction(function () use ($scrapReceipt, $data, $userId) {
            $document = ScrapReceiptDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($scrapReceipt->id);
            $this->assertLatestMovement($document);
            $old = $document->load(['scrapProduct', 'sourceProduct'])->toArray();
            $oldProduct = Product::query()->lockForUpdate()->findOrFail($document->scrap_product_id);
            $scrap = $this->scrapProduct((int) $data['scrap_product_id']);
            $source = Product::query()->where('track_stock', true)->where('is_active', true)->lockForUpdate()->findOrFail($data['source_product_id']);
            $this->move($document, $oldProduct, -(float) $document->quantity, $userId, 'scrap_receipt_reversal');
            $document->update([
                'receipt_date' => $data['receipt_date'], 'scrap_product_id' => $scrap->id, 'source_product_id' => $source->id,
                'production_number' => $data['production_number'] ?? null, 'quantity' => $data['quantity'], 'description' => $data['description'] ?? null,
            ]);
            $this->move($document, $scrap, (float) $document->quantity, $userId, 'scrap_receipt');
            $this->audit->log('UPDATE', 'SCRAP_RECEIPTS', "Memperbarui penerimaan scrap {$document->number}", $document, $old, $document->fresh(['scrapProduct', 'sourceProduct'])->toArray(), $userId);
            return $document->fresh(['scrapProduct.unit', 'sourceProduct.unit', 'creator']);
        });
    }

    public function delete(ScrapReceiptDocument $scrapReceipt, int $userId): void
    {
        DB::transaction(function () use ($scrapReceipt, $userId) {
            $document = ScrapReceiptDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($scrapReceipt->id);
            $this->assertLatestMovement($document);
            $document->load(['scrapProduct', 'sourceProduct']);
            $old = $document->toArray();
            $this->move($document, $document->scrapProduct, -(float) $document->quantity, $userId, 'scrap_receipt_reversal');
            $document->delete();
            $this->audit->log('DELETE', 'SCRAP_RECEIPTS', "Membatalkan penerimaan scrap {$document->number}", $document, $old, ['deleted_at' => now()->toDateTimeString()], $userId);
        });
    }

    private function scrapProduct(int $id): Product
    {
        return app(\App\Domain\Inventory\Queries\ScrapReceiptTableQuery::class)->scrapProducts()->lockForUpdate()->findOrFail($id);
    }

    private function assertLatestMovement(ScrapReceiptDocument $document): void
    {
        $last = StockMovement::query()->where('scrap_receipt_document_id', $document->id)->max('id');
        if (!$last) throw ValidationException::withMessages(['scrap_product_id' => __('ui.scrap_receipt.movement_missing')]);
        if (StockMovement::query()->where('product_id', $document->scrap_product_id)->where('id', '>', $last)->exists()) {
            throw ValidationException::withMessages(['scrap_product_id' => __('ui.scrap_receipt.later_movement')]);
        }
    }

    private function move(ScrapReceiptDocument $document, Product $product, float $quantity, int $userId, string $type): void
    {
        $stock = ProductStock::query()->firstOrCreate(['product_id' => $product->id], ['quantity' => 0, 'average_unit_cost' => 0]);
        $stock = ProductStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
        $before = round((float) $stock->quantity, 4);
        $after = round($before + $quantity, 4);
        if ($after < 0) throw ValidationException::withMessages(['quantity' => __('ui.scrap_receipt.stock_shortage')]);
        $stock->update(['quantity' => $after]);
        StockMovement::query()->create([
            'product_id' => $product->id, 'scrap_receipt_document_id' => $document->id, 'created_by' => $userId,
            'movement_type' => $type, 'quantity' => $quantity, 'quantity_before' => $before, 'quantity_after' => $after,
            'cost_amount' => 0, 'notes' => __('ui.scrap_receipt.stock_reference', ['number' => $document->number]),
        ]);
    }
}

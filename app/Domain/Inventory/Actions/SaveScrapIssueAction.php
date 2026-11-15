<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Queries\ScrapIssueTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{Customer, Product, ProductStock, ScrapIssueDocument, StockMovement};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveScrapIssueAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $audit, private DocumentNumberService $numbers) {}

    public function create(array $data, int $userId): ScrapIssueDocument
    {
        return DB::transaction(function () use ($data, $userId) {
            $product = $this->product((int) $data['scrap_product_id']);
            Customer::query()->where('is_active', true)->findOrFail($data['customer_id']);
            $document = ScrapIssueDocument::query()->create([
                'company_id' => $this->company->id(), 'number' => $this->numbers->generate('scrap_issue'),
                'issue_date' => $data['issue_date'], 'customer_id' => $data['customer_id'], 'scrap_product_id' => $product->id,
                'quantity' => $data['quantity'], 'description' => $data['description'] ?? null, 'created_by' => $userId,
            ]);
            $this->move($document, $product, -(float) $document->quantity, $userId, 'scrap_issue');
            $this->audit->log('CREATE', 'SCRAP_ISSUES', "Mencatat pengeluaran scrap {$document->number}", $document, null, $document->load(['customer', 'scrapProduct'])->toArray(), $userId);
            return $document->load(['customer', 'scrapProduct.unit', 'creator']);
        });
    }

    public function update(ScrapIssueDocument $scrapIssue, array $data, int $userId): ScrapIssueDocument
    {
        return DB::transaction(function () use ($scrapIssue, $data, $userId) {
            $document = ScrapIssueDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($scrapIssue->id);
            $this->assertLatest($document);
            $old = $document->load(['customer', 'scrapProduct'])->toArray();
            $oldProduct = Product::query()->lockForUpdate()->findOrFail($document->scrap_product_id);
            $product = $this->product((int) $data['scrap_product_id']);
            Customer::query()->where('is_active', true)->findOrFail($data['customer_id']);
            $this->move($document, $oldProduct, (float) $document->quantity, $userId, 'scrap_issue_reversal');
            $document->update(['issue_date' => $data['issue_date'], 'customer_id' => $data['customer_id'], 'scrap_product_id' => $product->id, 'quantity' => $data['quantity'], 'description' => $data['description'] ?? null]);
            $this->move($document, $product, -(float) $document->quantity, $userId, 'scrap_issue');
            $this->audit->log('UPDATE', 'SCRAP_ISSUES', "Memperbarui pengeluaran scrap {$document->number}", $document, $old, $document->fresh(['customer', 'scrapProduct'])->toArray(), $userId);
            return $document->fresh(['customer', 'scrapProduct.unit', 'creator']);
        });
    }

    public function delete(ScrapIssueDocument $scrapIssue, int $userId): void
    {
        DB::transaction(function () use ($scrapIssue, $userId) {
            $document = ScrapIssueDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($scrapIssue->id);
            $this->assertLatest($document);
            $document->load(['customer', 'scrapProduct']); $old = $document->toArray();
            $this->move($document, $document->scrapProduct, (float) $document->quantity, $userId, 'scrap_issue_reversal');
            $document->delete();
            $this->audit->log('DELETE', 'SCRAP_ISSUES', "Membatalkan pengeluaran scrap {$document->number}", $document, $old, ['deleted_at' => now()->toDateTimeString()], $userId);
        });
    }

    private function product(int $id): Product
    {
        return app(ScrapIssueTableQuery::class)->scrapProducts()->lockForUpdate()->findOrFail($id);
    }

    private function assertLatest(ScrapIssueDocument $document): void
    {
        $last = StockMovement::query()->where('scrap_issue_document_id', $document->id)->max('id');
        if (!$last) throw ValidationException::withMessages(['scrap_product_id' => __('ui.scrap_issue.movement_missing')]);
        if (StockMovement::query()->where('product_id', $document->scrap_product_id)->where('id', '>', $last)->exists()) {
            throw ValidationException::withMessages(['scrap_product_id' => __('ui.scrap_issue.later_movement')]);
        }
    }

    private function move(ScrapIssueDocument $document, Product $product, float $quantity, int $userId, string $type): void
    {
        $stock = ProductStock::query()->firstOrCreate(['product_id' => $product->id], ['quantity' => 0, 'average_unit_cost' => 0]);
        $stock = ProductStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
        $before = round((float) $stock->quantity, 4); $after = round($before + $quantity, 4);
        if ($after < 0) throw ValidationException::withMessages(['quantity' => __('ui.scrap_issue.stock_shortage')]);
        $stock->update(['quantity' => $after]);
        StockMovement::query()->create([
            'product_id' => $product->id, 'scrap_issue_document_id' => $document->id, 'created_by' => $userId,
            'movement_type' => $type, 'quantity' => $quantity, 'quantity_before' => $before, 'quantity_after' => $after,
            'cost_amount' => 0, 'notes' => __('ui.scrap_issue.stock_reference', ['number' => $document->number]),
        ]);
    }
}

<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{Product, ProductStock, StockMovement, WipIssueDocument, WipIssueLine};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveWipIssueAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $audit, private AutomaticJournalService $journals, private DocumentNumberService $numbers) {}

    public function create(array $data, int $userId): WipIssueLine
    {
        return DB::transaction(function () use ($data, $userId) {
            $product = $this->product((int) $data['product_id']);
            $document = WipIssueDocument::query()->create([
                'company_id' => $this->company->id(), 'number' => $this->numbers->generate('wip_issue'),
                'transaction_date' => $data['transaction_date'], 'created_by' => $userId,
            ]);
            $line = $document->items()->create(['product_id' => $product->id, 'quantity' => $data['quantity'], 'description' => $data['description'] ?? null]);
            $this->move($document, $line, $product, -(float) $line->quantity, $userId);
            $this->audit->log('CREATE', 'WIP_ISSUES', "Mengeluarkan WIP {$product->code} pada {$document->number}", $line, null, $line->load(['document', 'product'])->toArray(), $userId);
            return $line->load(['document', 'product.unit']);
        });
    }

    public function update(WipIssueLine $wipIssueLine, array $data, int $userId): WipIssueLine
    {
        return DB::transaction(function () use ($wipIssueLine, $data, $userId) {
            $line = WipIssueLine::query()->lockForUpdate()->findOrFail($wipIssueLine->id);
            $document = WipIssueDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($line->document_id);
            $old = $line->load(['document', 'product'])->toArray();
            $this->assertNoLaterMovement($line);
            $oldProduct = Product::query()->lockForUpdate()->findOrFail($line->product_id);
            $newProduct = $this->product((int) $data['product_id']);
            $this->move($document, $line, $oldProduct, (float) $line->quantity, $userId, 'wip_issue_reversal');
            $line->update(['product_id' => $newProduct->id, 'quantity' => $data['quantity'], 'description' => $data['description'] ?? null]);
            $document->update(['transaction_date' => $data['transaction_date']]);
            $this->move($document, $line, $newProduct, -(float) $line->quantity, $userId);
            $this->audit->log('UPDATE', 'WIP_ISSUES', "Memperbarui pengeluaran WIP {$document->number}", $line, $old, $line->fresh(['document', 'product'])->toArray(), $userId);
            return $line->fresh(['document', 'product.unit']);
        });
    }

    public function delete(WipIssueLine $wipIssueLine, int $userId): void
    {
        DB::transaction(function () use ($wipIssueLine, $userId) {
            $line = WipIssueLine::query()->lockForUpdate()->findOrFail($wipIssueLine->id);
            $document = WipIssueDocument::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($line->document_id);
            $line->load(['product', 'document']);
            $old = $line->toArray();
            $this->assertNoLaterMovement($line);
            $product = Product::query()->lockForUpdate()->findOrFail($line->product_id);
            $this->move($document, $line, $product, (float) $line->quantity, $userId, 'wip_issue_reversal');
            $line->delete();
            $document->delete();
            $this->audit->log('DELETE', 'WIP_ISSUES', "Membatalkan pengeluaran WIP {$document->number}", $line, $old, ['deleted_at' => now()->toDateTimeString()], $userId);
        });
    }

    private function product(int $id): Product
    {
        return app(\App\Domain\Inventory\Queries\WipIssueTableQuery::class)->products()->lockForUpdate()->findOrFail($id);
    }

    private function assertNoLaterMovement(WipIssueLine $line): void
    {
        $last = StockMovement::query()->where('wip_issue_line_id', $line->id)->max('id');
        if (!$last) throw ValidationException::withMessages(['item' => __('ui.wip_issue.movement_missing')]);
        $laterExists = StockMovement::query()->where('product_id', $line->product_id)->where('id', '>', $last)->exists();
        if ($laterExists) throw ValidationException::withMessages(['item' => __('ui.wip_issue.later_movement')]);
    }

    private function move(WipIssueDocument $document, WipIssueLine $line, Product $product, float $quantity, int $userId, ?string $type = null): void
    {
        if (abs($quantity) < 0.0000001) return;
        $stock = ProductStock::query()->firstOrCreate(['product_id' => $product->id], ['quantity' => 0, 'average_unit_cost' => 0]);
        $stock = ProductStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
        $before = round((float) $stock->quantity, 4);
        $after = round($before + $quantity, 4);
        if ($after < 0) throw ValidationException::withMessages(['quantity' => __('ui.wip_issue.insufficient_stock', ['item' => $product->name])]);
        $unitCost = (float) $stock->average_unit_cost;
        $stock->update(['quantity' => $after]);
        $movement = StockMovement::query()->create([
            'product_id' => $product->id, 'wip_issue_document_id' => $document->id, 'wip_issue_line_id' => $line->id,
            'created_by' => $userId, 'movement_type' => $type ?? 'wip_issue', 'quantity' => $quantity,
            'quantity_before' => $before, 'quantity_after' => $after,
            'cost_amount' => round(abs($quantity) * $unitCost, 2),
            'notes' => __('ui.wip_issue.stock_reference', ['number' => $document->number]),
        ]);
        $this->journals->recordStockMovement($movement, $userId);
    }
}

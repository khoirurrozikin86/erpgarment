<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Models\{ProductStock, ProductionReturnDocument, StockMovement};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveProductionReturnAction
{
    public function __construct(private AuditLogService $audit, private AutomaticJournalService $journals) {}

    public function __invoke(ProductionReturnDocument $document, int $companyId, int $userId): ProductionReturnDocument
    {
        return DB::transaction(function () use ($document, $companyId, $userId) {
            $locked = ProductionReturnDocument::query()->where('company_id', $companyId)->with('items.product')->lockForUpdate()->findOrFail($document->id);
            if ($locked->status !== 'pending' || $locked->items->isEmpty()) throw ValidationException::withMessages(['receipt' => __('ui.production_return.not_pending')]);
            foreach ($locked->items as $item) {
                $stock = ProductStock::query()->firstOrCreate(['product_id' => $item->product_id], ['quantity' => 0, 'average_unit_cost' => 0]);
                $stock = ProductStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
                $before = (float) $stock->quantity; $qty = (float) $item->quantity; $after = round($before + $qty, 4);
                $stock->update(['quantity' => $after]);
                $movement = StockMovement::query()->create([
                    'product_id' => $item->product_id, 'production_return_document_id' => $locked->id,
                    'production_return_item_id' => $item->id, 'created_by' => $userId,
                    'movement_type' => 'production_return', 'quantity' => $qty,
                    'quantity_before' => $before, 'quantity_after' => $after, 'cost_amount' => 0,
                    'notes' => "Penerimaan retur produksi {$locked->number}",
                ]);
                $this->journals->recordStockMovement($movement, $userId);
            }
            $old = ['status' => $locked->status];
            $locked->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now()]);
            $this->audit->log('APPROVE', 'RECEIPT_FROM_PRODUCTION', "Menyetujui penerimaan produksi {$locked->number}", $locked, $old, $locked->fresh()->toArray(), $userId);
            return $locked->fresh(['approver:id,name']);
        });
    }
}

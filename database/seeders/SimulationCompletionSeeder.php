<?php

namespace Database\Seeders;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Purchasing\Actions as Purchasing;
use App\Domain\Purchasing\DTOs as PurchasingData;
use App\Models\{AuditLog, Company, GoodsSentNote, Product, ProductStock, ProductionOrder, StockMovement, Supplier, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{Auth, DB};
use RuntimeException;

class SimulationCompletionSeeder extends Seeder
{
    public function run(): void
    {
        $context = app(CompanyContext::class);
        $previousCompany = $context->has() ? $context->get() : null;
        $previousUser = Auth::user();
        $company = Company::where('code', 'SD001')->where('is_active', true)->first()
            ?? Company::where('is_active', true)->firstOrFail();
        try {
            $context->set($company);
            DB::transaction(function () use ($company) {
                Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
                $marker = 'SIMULATION-V3-COMPANY-'.$company->id;
                if (AuditLog::where('module', 'SIMULATION')->where('description', $marker)->exists()) return;
                $actor = User::where('email', 'sim.operator@localhost.test')->firstOrFail();
                $reviewer = User::where('email', 'sim.reviewer@localhost.test')->firstOrFail();
                Auth::setUser($actor);
                $supplier = Supplier::where('code', 'SIM-SUPPLIER')->firstOrFail();
                $label = Product::where('code', 'SIM-LABEL')->firstOrFail();
                // Real application transitions leave examples in each purchasing queue.
                foreach (['pending_pr', 'draft_po', 'issued_po', 'rejected_mr'] as $scenario) {
                    $mr = app(Purchasing\CreateMaterialRequestAction::class)(PurchasingData\MaterialRequestData::fromArray([
                        'department' => 'SIM3-PPIC', 'reason' => 'SIM3-'.$scenario,
                        'items' => [['product_id' => $label->id, 'quantity' => 10]],
                    ]), $actor->id);
                    app(Purchasing\ReviewMaterialRequestAction::class)($mr, new PurchasingData\MaterialRequestReviewData(
                        $reviewer->id, $scenario === 'rejected_mr' ? 'rejected' : 'approved', 'SIM3-Latihan approval'));
                    if ($scenario === 'rejected_mr') continue;
                    $pr = app(Purchasing\CreatePurchaseRequestAction::class)(PurchasingData\PurchaseRequestData::fromArray([
                        'material_request_id' => $mr->id, 'supplier_id' => $supplier->id, 'notes' => 'SIM3-'.$scenario,
                        'items' => [['material_request_item_id' => $mr->items->first()->id, 'quantity' => 10, 'unit_price' => 1000]],
                    ]), $actor->id);
                    if ($scenario === 'pending_pr') continue;
                    app(Purchasing\ReviewPurchaseRequestAction::class)($pr, new PurchasingData\PurchaseRequestReviewData($reviewer->id, 'approved', 'SIM3-Latihan approval'));
                    $po = app(Purchasing\CreatePurchaseOrderAction::class)(PurchasingData\PurchaseOrderData::fromArray([
                        'purchase_request_id' => $pr->id, 'notes' => 'SIM3-'.$scenario,
                    ]), $actor->id);
                    if ($scenario === 'issued_po') app(Purchasing\IssuePurchaseOrderAction::class)($po);
                }
                $this->pendingProduction($company->id, $actor->id);
                $this->finishedGoodsReturns($company->id, $actor->id);
                AuditLog::create(['user_id' => $actor->id, 'action' => 'SEED', 'module' => 'SIMULATION',
                    'description' => $marker, 'new_values' => ['queues' => ['MR rejected', 'PR pending', 'PO draft/issued', 'Production receipt pending'], 'finished_goods_returns' => 3]]);
            });
        } finally {
            $previousCompany ? $context->set($previousCompany) : $context->clear();
            $previousUser ? Auth::setUser($previousUser) : Auth::forgetUser();
        }
    }

    private function pendingProduction(int $company, int $actor): void
    {
        $source = ProductionOrder::where('company_id', $company)->where('number', 'SIM-MO-001')->firstOrFail();
        $order = app(\App\Domain\Manufacturing\Actions\CreateProductionOrderAction::class)([
            'number' => 'SIM3-MO-PENDING', 'buyer_po_number' => 'SIM3-BUYER-PENDING', 'currency' => 'IDR',
            'po_date' => now()->toDateString(), 'due_date' => now()->addWeek()->toDateString(),
            'customer_id' => $source->customer_id, 'output_product_id' => $source->output_product_id, 'planned_quantity' => 5, 'price_per_unit' => 100000,
        ], $actor);
        $raw = app(\App\Domain\Manufacturing\Actions\CreateRawMaterialOrderAction::class)(
            \App\Domain\Manufacturing\DTOs\RawMaterialOrderData::fromArray(['order_date' => now()->toDateString(),
                'production_order_number' => $order->number, 'items' => $order->materials->map(fn ($material) => [
                    'production_order_material_id' => $material->id, 'quantity' => 5])->all()]), $actor);
        app(\App\Domain\Inventory\Actions\IssueRawMaterialOrderItemAction::class)->issueMany(
            $raw->items->map(fn ($item) => ['raw_material_order_item_id' => $item->id, 'quantity' => 5])->all(), now()->toDateString(), $actor);
        // Deliberately not confirmed at production: leaves the receipt approval queue populated.
    }

    private function finishedGoodsReturns(int $company, int $actor): void
    {
        // The customer-return entry module was removed, but the report still reads this event type.
        // Seed stock-only demo return events against actual approved GSN headers/details.
        // No refund, tax, revenue, or new production business rule is inferred here.
        $notes = GoodsSentNote::where('company_id', $company)->whereNotNull('approved_at')
            ->whereHas('items.productionOrder', fn ($q) => $q->where('number', 'like', 'SIM2-MO-%'))
            ->orderBy('id')->with('items')->take(3)->get();
        if ($notes->count() !== 3) throw new RuntimeException('Tiga GSN SIM2 disetujui diperlukan untuk simulasi retur.');
        foreach ($notes as $note) {
            $item = $note->items->first();
            $returned = StockMovement::where('goods_sent_note_item_id', $item->id)->where('movement_type', 'customer_return')->sum('quantity');
            if ((float) $returned + 1 > (float) $item->quantity) throw new RuntimeException('Retur simulasi melebihi jumlah GSN.');
            $shipment = StockMovement::where('goods_sent_note_item_id', $item->id)->where('movement_type', 'goods_sent_note')->firstOrFail();
            $unitCost = $shipment->lotAllocations()->get()->sum(fn ($allocation) => abs((float) $allocation->quantity) * (float) $allocation->unit_cost) / (float) $item->quantity;
            $stock = ProductStock::where('product_id', $item->product_id)->lockForUpdate()->firstOrFail();
            $before = (float) $stock->quantity;
            $after = round($before + 1, 4);
            $stock->update(['quantity' => $after, 'average_unit_cost' => round(($before * (float) $stock->average_unit_cost + $unitCost) / $after, 6)]);
            $movement = StockMovement::create(['product_id' => $item->product_id, 'goods_sent_note_id' => $note->id,
                'goods_sent_note_item_id' => $item->id, 'created_by' => $actor, 'movement_type' => 'customer_return',
                'quantity' => 1, 'quantity_before' => $before, 'quantity_after' => $after, 'cost_amount' => round($unitCost, 2),
                'notes' => 'SIM3-RET-'.$note->id.' — retur stok 1 PCS dari GSN '.$note->number.' (simulasi tanpa refund)']);
            app(\App\Domain\Audit\Services\AuditLogService::class)->log('SEED', 'FINISHED_GOODS_RETURN',
                $movement->notes, $movement, null, ['source_gsn' => $note->id, 'source_item' => $item->id, 'movement' => $movement->toArray()], $actor);
            $lotBalance = \App\Models\InventoryLot::where('product_id', $item->product_id)->sum('current_quantity');
            if (abs((float) $lotBalance - $after) > 0.0001) throw new RuntimeException('Saldo lot retur simulasi tidak cocok.');
        }
    }
}

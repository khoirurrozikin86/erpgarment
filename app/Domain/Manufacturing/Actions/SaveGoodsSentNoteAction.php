<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{GoodsSentNote, GoodsSentNoteItem, InventoryLot, Product, ProductStock, ProductionOrder, StockMovement};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveGoodsSentNoteAction
{
    public function __construct(private CompanyContext $company, private DocumentNumberService $numbers, private AuditLogService $audit) {}

    public function create(array $data, int $userId): GoodsSentNote
    {
        return DB::transaction(function () use ($data, $userId) {
            $note = GoodsSentNote::query()->create($this->header($data) + ['company_id' => $this->company->id(), 'number' => $this->numbers->generate('goods_sent_note'), 'created_by' => $userId]);
            foreach ($data['items'] as $line) $this->addLine($note, $line, $userId);
            $this->audit->log('CREATE', 'GOODS_SENT_NOTE', "Membuat surat jalan {$note->number}", $note, null, $note->load('items')->toArray(), $userId);
            return $note->load('items');
        });
    }

    public function update(GoodsSentNote $note, array $data, int $userId): GoodsSentNote
    {
        return DB::transaction(function () use ($note, $data, $userId) {
            $note = GoodsSentNote::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($note->id);
            $old = $note->load('items')->toArray();
            foreach ($note->items as $item) {
                $this->assertNoLaterMovement($item, $note->id);
                $this->moveStock($note, $item, (float) $item->quantity, $userId, 'goods_sent_note_reversal');
                $item->delete();
            }
            $note->update($this->header($data));
            foreach ($data['items'] as $line) $this->addLine($note, $line, $userId);
            $this->audit->log('UPDATE', 'GOODS_SENT_NOTE', "Memperbarui surat jalan {$note->number}", $note, $old, $note->fresh()->load('items')->toArray(), $userId);
            return $note->fresh()->load('items');
        });
    }

    public function delete(GoodsSentNote $note, int $userId): void
    {
        DB::transaction(function () use ($note, $userId) {
            $note = GoodsSentNote::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($note->id);
            $old = $note->load('items')->toArray();
            foreach ($note->items as $item) {
                $this->assertNoLaterMovement($item, $note->id);
                $this->moveStock($note, $item, (float) $item->quantity, $userId, 'goods_sent_note_reversal');
                $item->delete();
            }
            $note->delete();
            $this->audit->log('DELETE', 'GOODS_SENT_NOTE', "Menghapus surat jalan {$note->number}", $note, $old, ['deleted_at' => now()->toDateTimeString()], $userId);
        });
    }

    public function approve(GoodsSentNote $note, int $userId): GoodsSentNote
    {
        return DB::transaction(function () use ($note, $userId) {
            $note = GoodsSentNote::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($note->id);
            if ($note->approved_at) throw ValidationException::withMessages(['note' => __('ui.delivery_note_approval.already_approved')]);
            $items = $note->items()->lockForUpdate()->get();
            if ($items->isEmpty()) throw ValidationException::withMessages(['note' => __('ui.delivery_note_approval.empty_note')]);

            $note->forceFill(['approved_by' => $userId, 'approved_at' => now()])->save();
            $this->audit->log('APPROVE', 'GOODS_SENT_NOTE', "Menyetujui surat jalan {$note->number}", $note, ['approved_at' => null], ['approved_by' => $userId, 'approved_at' => $note->approved_at->toDateTimeString()], $userId);
            return $note->fresh(['items', 'approver']);
        });
    }

    public function cancelApproval(GoodsSentNote $note, int $userId): GoodsSentNote
    {
        return DB::transaction(function () use ($note, $userId) {
            $note = GoodsSentNote::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($note->id);
            if (!$note->approved_at) throw ValidationException::withMessages(['note' => __('ui.delivery_note_approval.not_approved')]);
            $before = ['approved_by' => $note->approved_by, 'approved_at' => $note->approved_at->toDateTimeString()];
            $note->forceFill(['approved_by' => null, 'approved_at' => null])->save();
            $this->audit->log('CANCEL_APPROVAL', 'GOODS_SENT_NOTE', "Membatalkan approval surat jalan {$note->number}", $note, $before, ['approved_by' => null, 'approved_at' => null], $userId);
            return $note->fresh(['items', 'approver']);
        });
    }

    private function header(array $data): array
    {
        return [
            'sent_date' => $data['sent_date'], 'contract_number' => $data['contract_number'] ?? null, 'contract_date' => $data['contract_date'] ?? null,
            'delivery_route' => $data['delivery_route'] ?? null, 'warehouse' => $data['warehouse'] ?? null, 'vehicle_number' => $data['vehicle_number'] ?? null,
            'driver_phone' => $data['driver_phone'] ?? null, 'driver_name' => $data['driver_name'] ?? null, 'seal_number' => $data['seal_number'] ?? null,
            'receiver_type' => $data['receiver_type'] ?? null, 'container_number' => $data['container_number'] ?? null,
        ];
    }

    private function addLine(GoodsSentNote $note, array $line, int $userId): void
    {
        $order = ProductionOrder::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($line['production_order_id']);
        $product = Product::query()->whereKey($order->output_product_id)->where('track_stock', true)->lockForUpdate()->first();
        if (!$product || (int) $product->id !== (int) $line['product_id']) throw ValidationException::withMessages(['items' => __('ui.gsn.invalid_product')]);
        $finished = (float) $order->stageEntries()->where('stage', 'finishing')->sum('good_quantity');
        $sent = (float) GoodsSentNoteItem::query()->where('production_order_id', $order->id)->whereHas('note', fn ($q) => $q->where('company_id', $this->company->id()))->sum('quantity');
        $qty = round((float) $line['quantity'], 4);
        if ($qty > $finished - $sent + 0.0001) throw ValidationException::withMessages(['items' => __('ui.gsn.exceeds_finished_goods', ['mo' => $order->number])]);
        $item = $note->items()->create(['production_order_id' => $order->id, 'product_id' => $product->id, 'customer_id' => $line['customer_id'] ?? $order->customer_id, 'quantity' => $qty, 'cartons' => $line['cartons'] ?? 0]);
        $this->moveStock($note, $item, -$qty, $userId, 'goods_sent_note');
    }

    private function moveStock(GoodsSentNote $note, GoodsSentNoteItem $item, float $quantity, int $userId, string $type): void
    {
        $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->firstOrFail();
        $stock = ProductStock::query()->firstOrCreate(['product_id' => $product->id], ['quantity' => 0, 'average_unit_cost' => 0]);
        $stock = ProductStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
        $before = round((float) $stock->quantity, 4);
        $after = round($before + $quantity, 4);
        if ($after < 0) throw ValidationException::withMessages(['items' => __('ui.gsn.insufficient_stock', ['item' => $product->name])]);
        $stock->update(['quantity' => $after]);
        StockMovement::query()->create([
            'product_id' => $product->id, 'goods_sent_note_id' => $note->id, 'goods_sent_note_item_id' => $item->id,
            'created_by' => $userId, 'movement_type' => $type, 'quantity' => $quantity,
            'quantity_before' => $before, 'quantity_after' => $after, 'cost_amount' => 0,
            'notes' => __('ui.gsn.stock_reference', ['number' => $note->number]),
        ]);
    }

    private function assertNoLaterMovement(GoodsSentNoteItem $item, int $noteId): void
    {
        $last = StockMovement::query()->where('goods_sent_note_item_id', $item->id)->whereIn('movement_type', ['goods_sent_note', 'goods_sent_note_reversal'])->latest('id')->first();
        if (!$last) return;
        $later = StockMovement::query()->where('product_id', $item->product_id)->where('id', '>', $last->id)->where(function ($q) use ($noteId) { $q->whereNull('goods_sent_note_id')->orWhere('goods_sent_note_id', '!=', $noteId); })->exists();
        if ($later) throw ValidationException::withMessages(['note' => __('ui.gsn.later_stock_movement')]);
    }
}

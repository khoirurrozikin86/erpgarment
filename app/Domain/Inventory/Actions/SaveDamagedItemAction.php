<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{DamagedItemDocument, DamagedItemLine, Product, ProductStock, StockMovement};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveDamagedItemAction
{
    public function __construct(private AuditLogService $audit, private AutomaticJournalService $journals, private DocumentNumberService $numbers) {}

    public function create(array $data, int $userId): DamagedItemLine
    {
        return DB::transaction(function () use ($data, $userId) {
            $document = DamagedItemDocument::query()->create([
                'number' => $this->numbers->generate('damaged_item'),
                'document_date' => $data['document_date'],
                'created_by' => $userId,
            ]);
            $product = Product::query()->where('track_stock', true)->where('is_active', true)->lockForUpdate()->findOrFail($data['product_id']);
            $line = $document->items()->create([
                'product_id' => $product->id,
                'quantity' => $data['quantity'],
                'description' => $data['description'] ?? null,
            ]);
            $this->applyMovement($document, $line, $product, -(float) $line->quantity, $userId, $line->description);
            $this->audit->log('CREATE', 'DAMAGED_ITEMS', "Mencatat barang rusak {$product->code} pada berita acara {$document->number}", $line, null, $line->load(['document', 'product'])->toArray(), $userId);
            return $line->load(['document', 'product.unit']);
        });
    }

    public function update(DamagedItemLine $damagedItemLine, array $data, int $userId): DamagedItemLine
    {
        return DB::transaction(function () use ($damagedItemLine, $data, $userId) {
            $line = DamagedItemLine::query()->lockForUpdate()->findOrFail($damagedItemLine->id);
            $old = $line->load(['document', 'product'])->toArray();
            $document = DamagedItemDocument::query()->lockForUpdate()->findOrFail($line->damaged_item_document_id);
            $product = Product::query()->where('track_stock', true)->lockForUpdate()->findOrFail($data['product_id']);
            $document->update(['document_date' => $data['document_date']]);
            $previousProductId = $line->product_id;
            $previousQuantity = (float) $line->quantity;
            $line->update(['product_id' => $product->id, 'quantity' => $data['quantity'], 'description' => $data['description'] ?? null]);
            $quantityDelta = (float) $line->quantity - $previousQuantity;
            if ($previousProductId === $product->id) {
                if (abs($quantityDelta) > 0.0000001) $this->applyMovement($document, $line, $product, -$quantityDelta, $userId, 'Koreksi barang rusak: ' . ($line->description ?? ''));
            } else {
                $oldProduct = Product::query()->lockForUpdate()->findOrFail($previousProductId);
                $this->applyMovement($document, $line, $oldProduct, $previousQuantity, $userId, 'Pembalik koreksi barang rusak');
                $this->applyMovement($document, $line, $product, -(float) $line->quantity, $userId, 'Koreksi barang rusak: ' . ($line->description ?? ''));
            }
            $this->audit->log('UPDATE', 'DAMAGED_ITEMS', "Memperbarui barang rusak {$document->number}", $line, $old, $line->fresh(['document', 'product'])->toArray(), $userId);
            return $line->fresh(['document', 'product.unit']);
        });
    }

    public function delete(DamagedItemLine $damagedItemLine, int $userId): void
    {
        DB::transaction(function () use ($damagedItemLine, $userId) {
            $line = DamagedItemLine::query()->lockForUpdate()->findOrFail($damagedItemLine->id);
            $line->load(['document', 'product']);
            $old = $line->toArray();
            $this->applyMovement($line->document, $line, $line->product, (float) $line->quantity, $userId, 'Pembatalan pencatatan barang rusak');
            $line->delete();
            $this->audit->log('DELETE', 'DAMAGED_ITEMS', "Membatalkan pencatatan barang rusak {$line->document->number}", $line, $old, ['deleted_at' => $line->deleted_at?->toDateTimeString()], $userId);
        });
    }

    private function applyMovement(DamagedItemDocument $document, DamagedItemLine $line, Product $product, float $quantity, int $userId, ?string $notes): void
    {
        if (abs($quantity) < 0.0000001) return;
        $stock = ProductStock::query()->firstOrCreate(['product_id' => $product->id], ['quantity' => 0, 'average_unit_cost' => 0]);
        $stock = ProductStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
        $before = (float) $stock->quantity;
        $after = round($before + $quantity, 4);
        if ($after < 0) throw ValidationException::withMessages(['quantity' => "Stok {$product->name} tidak mencukupi."]);
        $cost = round(abs($quantity) * ((float) $stock->average_unit_cost > 0 ? (float) $stock->average_unit_cost : (float) $product->purchase_price), 2);
        $stock->update(['quantity' => $after]);
        $movement = StockMovement::create([
            'product_id' => $product->id,
            'damaged_item_document_id' => $document->id,
            'damaged_item_line_id' => $line->id,
            'created_by' => $userId,
            'movement_type' => $quantity < 0 ? 'damaged_item' : 'damaged_item_reversal',
            'quantity' => $quantity, 'quantity_before' => $before, 'quantity_after' => $after,
            'cost_amount' => $cost, 'notes' => trim(($notes ?? '') . " — Berita Acara {$document->number}"),
        ]);
        $this->journals->recordStockMovement($movement, $userId);
    }
}

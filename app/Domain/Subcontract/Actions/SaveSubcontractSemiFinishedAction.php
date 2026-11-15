<?php

namespace App\Domain\Subcontract\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{SubcontractSemiFinishedDocument, SubcontractSemiFinishedItem};
use Illuminate\Support\Facades\DB;

class SaveSubcontractSemiFinishedAction
{
    public function create(array $data, int $companyId, int $userId): SubcontractSemiFinishedDocument
    {
        return DB::transaction(function () use ($data, $companyId, $userId) {
            $number = app(DocumentNumberService::class)->generate('subcontract_semi_finished');
            $document = SubcontractSemiFinishedDocument::query()->create([
                'company_id' => $companyId,
                'number' => $number,
                'production_date' => $data['production_date'],
                'production_order_id' => $data['production_order_id'] ?? null,
                'buyer_po_number' => $data['buyer_po_number'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => $userId,
            ]);
            $document->items()->create(['product_id' => $data['product_id'], 'quantity' => $data['quantity']]);
            app(AuditLogService::class)->log('CREATE', 'SUBCONTRACT_SEMI_FINISHED', "Created subcontract semi-finished production {$number}", $document, null, $this->snapshot($document, $data), $userId);

            return $document;
        });
    }

    public function update(SubcontractSemiFinishedDocument $document, array $data, int $userId): SubcontractSemiFinishedDocument
    {
        return DB::transaction(function () use ($document, $data, $userId) {
            $before = $this->snapshot($document, $document->items()->first()?->only(['product_id', 'quantity']) ?? []);
            $document->update([
                'production_date' => $data['production_date'],
                'production_order_id' => $data['production_order_id'] ?? null,
                'buyer_po_number' => $data['buyer_po_number'] ?? null,
                'description' => $data['description'] ?? null,
            ]);
            $item = $document->items()->first();
            if ($item) {
                $item->update(['product_id' => $data['product_id'], 'quantity' => $data['quantity']]);
                $document->items()->where('id', '!=', $item->id)->delete();
            } else {
                $document->items()->create(['product_id' => $data['product_id'], 'quantity' => $data['quantity']]);
            }
            app(AuditLogService::class)->log('UPDATE', 'SUBCONTRACT_SEMI_FINISHED', "Updated subcontract semi-finished production {$document->number}", $document, $before, $this->snapshot($document, $data), $userId);

            return $document->refresh();
        });
    }

    public function delete(SubcontractSemiFinishedDocument $document, int $userId): void
    {
        DB::transaction(function () use ($document, $userId) {
            $before = $this->snapshot($document, $document->items()->first()?->only(['product_id', 'quantity']) ?? []);
            $number = $document->number;
            $document->items()->delete();
            $document->delete();
            app(AuditLogService::class)->log('DELETE', 'SUBCONTRACT_SEMI_FINISHED', "Deleted subcontract semi-finished production {$number}", $document, $before, null, $userId);
        });
    }

    private function snapshot(SubcontractSemiFinishedDocument $document, array $item): array
    {
        return [
            'number' => $document->number,
            'production_date' => $document->production_date?->toDateString(),
            'production_order_id' => $document->production_order_id,
            'buyer_po_number' => $document->buyer_po_number,
            'description' => $document->description,
            'product_id' => $item['product_id'] ?? null,
            'quantity' => $item['quantity'] ?? null,
        ];
    }
}

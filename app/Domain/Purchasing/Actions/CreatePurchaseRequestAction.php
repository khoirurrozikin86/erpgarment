<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Purchasing\DTOs\PurchaseRequestData;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\MaterialRequest;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePurchaseRequestAction
{
    public function __construct(
        private DocumentNumberService $numbers,
        private AuditLogService $auditLog,
        private SyncMaterialRequestPurchaseStatusAction $syncPurchaseStatus,
    ) {}

    public function __invoke(PurchaseRequestData $data, int $userId): PurchaseRequest
    {
        return DB::transaction(function () use ($data, $userId) {
            $materialRequest = MaterialRequest::query()
                ->with('items.product')
                ->lockForUpdate()
                ->findOrFail($data->materialRequestId);

            if ($materialRequest->status !== 'approved') {
                throw ValidationException::withMessages(['material_request_id' => 'Material Request harus disetujui sebelum dibuatkan Purchase Request.']);
            }
            $supplier = Supplier::query()->where('is_active', true)->find($data->supplierId);
            if (!$supplier) {
                throw ValidationException::withMessages(['supplier_id' => 'Supplier tidak aktif atau tidak ditemukan.']);
            }

            $sourceItems = $materialRequest->items->keyBy('id');
            $submittedItems = collect($data->items)->keyBy(fn ($item) => (int) $item['material_request_item_id']);
            if ($submittedItems->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Pilih setidaknya satu barang untuk Purchase Request.']);
            }
            $reservedQuantities = PurchaseRequestItem::query()
                ->whereIn('material_request_item_id', $sourceItems->keys())
                ->whereHas('purchaseRequest', fn ($query) => $query->whereIn('status', ['pending', 'approved']))
                ->selectRaw('material_request_item_id, SUM(quantity) as allocated_quantity')
                ->groupBy('material_request_item_id')
                ->pluck('allocated_quantity', 'material_request_item_id');
            foreach ($submittedItems as $sourceItemId => $submittedItem) {
                if (!$sourceItems->has($sourceItemId)) {
                    throw ValidationException::withMessages(['items' => 'Barang Purchase Request harus berasal dari Material Request yang dipilih.']);
                }
                $requestedQuantity = (float) $sourceItems[$sourceItemId]->quantity;
                $alreadyAllocated = (float) ($reservedQuantities[$sourceItemId] ?? 0);
                $availableQuantity = max(0, $requestedQuantity - $alreadyAllocated);
                $quantity = (float) $submittedItem['quantity'];
                if ($quantity <= 0 || $quantity - $availableQuantity > 0.0000001) {
                    throw ValidationException::withMessages([
                        "items.{$sourceItemId}.quantity" => "Jumlah melebihi sisa kebutuhan Material Request (sisa: {$availableQuantity}).",
                    ]);
                }
            }

            $total = 0.0;
            $request = PurchaseRequest::create([
                'number' => $this->numbers->generate('purchase_request'),
                'material_request_id' => $materialRequest->id,
                'supplier_id' => $supplier->id,
                'requested_by' => $userId,
                'notes' => $data->notes,
                'status' => 'pending',
                'total_amount' => 0,
            ]);

            foreach ($sourceItems as $sourceItem) {
                if (!$submittedItems->has($sourceItem->id)) continue;
                $submitted = $submittedItems->get($sourceItem->id);
                $quantity = (float) $submitted['quantity'];
                $unitPrice = round((float) $submitted['unit_price'], 2);
                $lineTotal = round($quantity * $unitPrice, 2);
                $total += $lineTotal;
                $request->items()->create([
                    'material_request_item_id' => $sourceItem->id,
                    'product_id' => $sourceItem->product_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);
            }

            $request->update(['total_amount' => round($total, 2)]);
            ($this->syncPurchaseStatus)($materialRequest);
            $this->auditLog->log(
                action: 'CREATE', module: 'PURCHASE_REQUEST',
                description: "Mengajukan Purchase Request {$request->number}",
                model: $request, oldValues: null,
                newValues: $request->load('items')->toArray(),
            );

            return $request->load(['materialRequest', 'supplier', 'requester', 'items.product.unit']);
        });
    }
}

<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Purchasing\DTOs\MaterialRequestData;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\MaterialRequest;
use Illuminate\Support\Facades\DB;

class CreateMaterialRequestAction
{
    public function __construct(
        private DocumentNumberService $numbers,
        private AuditLogService $auditLog,
    ) {}

    public function __invoke(MaterialRequestData $data, int $userId): MaterialRequest
    {
        return DB::transaction(function () use ($data, $userId) {
            $request = MaterialRequest::create([
                ...$data->toArray(),
                'number' => $this->numbers->generate('material_request'),
                'requested_by' => $userId,
                'status' => 'pending',
            ]);
            $request->items()->createMany($data->items);

            $this->auditLog->log(
                action: 'CREATE', module: 'MATERIAL_REQUEST',
                description: "Mengajukan Material Request {$request->number}",
                model: $request, oldValues: null, newValues: $request->load('items')->toArray(),
            );

            return $request->load(['requester', 'items.product.unit']);
        });
    }
}

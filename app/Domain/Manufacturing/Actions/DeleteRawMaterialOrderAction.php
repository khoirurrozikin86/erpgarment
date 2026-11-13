<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\RawMaterialOrderDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteRawMaterialOrderAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $audit) {}

    public function __invoke(RawMaterialOrderDocument $document, int $userId): void
    {
        DB::transaction(function () use ($document, $userId) {
            $document = RawMaterialOrderDocument::query()->where('company_id', $this->company->id())->with('items')->lockForUpdate()->findOrFail($document->id);
            if ($document->items->contains(fn ($item) => (float) $item->issued_quantity > 0)) {
                throw ValidationException::withMessages(['document' => __('ui.raw_material_order.issued_locked')]);
            }
            $old = $document->toArray();
            $this->audit->log('DELETE', 'RAW_MATERIAL_ORDERS', "Menghapus Order Bahan Baku {$document->number}", $document, $old, null, $userId);
            $document->items()->delete();
            $document->delete();
        });
    }
}

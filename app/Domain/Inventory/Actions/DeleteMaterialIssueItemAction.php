<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\MaterialIssueItem;
use Illuminate\Support\Facades\DB;

class DeleteMaterialIssueItemAction
{
    public function __construct(
        private CompanyContext $company,
        private RestoreMaterialIssueItemAction $restore,
        private AuditLogService $audit,
    ) {}

    public function __invoke(MaterialIssueItem $issueItem, int $userId): void
    {
        DB::transaction(function () use ($issueItem, $userId) {
            $item = MaterialIssueItem::query()->with(['document', 'product'])->lockForUpdate()->findOrFail($issueItem->id);
            if (!$item->raw_material_order_item_id || (int) $item->document->company_id !== $this->company->id()) abort(404);

            $document = $item->document;
            $oldValues = ['document' => $document->toArray(), 'item' => $item->toArray()];
            ($this->restore)($item, $userId);

            $this->audit->log(
                action: 'DELETE', module: 'MATERIAL_ISSUE',
                description: __('ui.material_issue.deleted', ['number' => $document->number]),
                model: $document, oldValues: $oldValues,
                newValues: ['stock_reversed' => true], userId: $userId,
            );

            $item->delete();
            if (!$document->items()->exists()) $document->delete();
        });
    }
}

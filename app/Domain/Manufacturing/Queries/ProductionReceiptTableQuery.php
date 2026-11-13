<?php

namespace App\Domain\Manufacturing\Queries;

use App\Models\MaterialIssueDocument;
use Illuminate\Database\Eloquent\Builder;

class ProductionReceiptTableQuery
{
    public function forCompany(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return MaterialIssueDocument::query()
            ->where('company_id', $companyId)
            ->whereNotNull('raw_material_order_document_id')
            ->with(['rawMaterialOrder:id,number', 'productionReceiver:id,name'])
            ->withCount('items')
            ->when($from, fn (Builder $query) => $query->whereDate('issue_date', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('issue_date', '<=', $to))
            ->orderByDesc('issue_date')->orderByDesc('id');
    }
}

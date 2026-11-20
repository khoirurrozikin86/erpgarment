<?php

namespace App\Domain\Customs\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class Bc23Query
{
    public function builder(int $companyId, array $filters): Builder
    {
        $documents = DB::table('customs_documents as d')
            ->leftJoin('customs_document_items as i', 'i.document_id', '=', 'd.id')
            ->leftJoin('suppliers as s', 's.id', '=', 'd.supplier_id')
            ->where('d.company_id', $companyId)
            ->where('d.document_type', 'BC 2.3')->whereNull('d.deleted_at')
            ->whereBetween('d.document_date', [$filters['from_date'], $filters['to_date']])
            ->select([
                'd.id as document_id', 'd.submission_number', 'd.document_number',
                'd.document_date', 'd.currency', 's.name as supplier_name', 'i.receipt_number',
                'i.receipt_date', 'i.item_code', 'i.item_name', 'i.unit', 'i.quantity', 'i.unit_price',
                'i.total_price', 'i.cif', 'i.country_code', 'i.hs_code',
            ]);

        return BcDocumentVariantQuery::rows($documents, $filters['search'] ?? null, 'receipt_number', 'supplier_name');
    }
}

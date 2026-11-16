<?php

namespace App\Domain\Subcontract\Queries;

class SubcontractDataTable
{
    public static function columns(string $kind): array
    {
        $table = 'subcontract_material_issue_items';
        $document = fn (string $field) => "(SELECT {$field} FROM subcontract_material_issue_documents WHERE id = {$table}.document_id LIMIT 1)";
        $product = fn (string $field) => "(SELECT {$field} FROM products WHERE id = {$table}.product_id LIMIT 1)";
        $subcontractor = '(SELECT name FROM subcontractors WHERE id = subcontract_contracts.subcontractor_id LIMIT 1)';
        if ($kind === 'contract-monitoring') return [null, $subcontractor, 'contract_number', 'bc_decree_number', 'description', 'starts_on', 'ends_on', 'quota'];
        if ($kind === 'goods-monitoring') return [null, null, $subcontractor, 'contract_number', 'bc_decree_number', 'starts_on', 'ends_on', 'quota', 'description'];
        if ($kind === 'goods-details') return [null, 'style', $product('code'), $product('name'), null, null, 'realized_quantity', null];
        return [null, null, $document('number'), $document('issue_date'), null, null,
            ...($kind === 'receipt' ? [$document('invoice_number'), $document('invoice_date')] : []),
            'style', $product('code'), $product('name'), null, 'quantity', $document('status'), null, $document('confirmed_at')];
    }
}

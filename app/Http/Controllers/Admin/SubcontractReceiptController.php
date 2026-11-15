<?php
namespace App\Http\Controllers\Admin;

use App\Domain\Subcontract\Queries\{SubcontractMaterialIssueQuery,SubcontractReceiptQuery};

class SubcontractReceiptController extends SubcontractMaterialIssueController
{
    protected string $issueKind = 'receipt';
    protected string $routePrefix = 'super.subcontract.receipts';
    protected string $translation = 'subcontract_receipt';
    protected string $permissionPrefix = 'subcontract-receipts';
    protected string $numberingType = 'subcontract_receipt';
    protected function query(): SubcontractMaterialIssueQuery { return app(SubcontractReceiptQuery::class); }
}

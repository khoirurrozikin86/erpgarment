<?php
namespace App\Http\Controllers\Admin;

use App\Domain\Subcontract\Queries\{SubcontractMaterialIssueQuery,SubcontractWipIssueQuery};

class SubcontractWipIssueController extends SubcontractMaterialIssueController
{
    protected string $issueKind = 'wip';
    protected string $permissionPrefix = 'subcontract-wip-issues';
    protected string $numberingType = 'subcontract_wip_issue';
    protected string $routePrefix = 'super.subcontract.wip-issues';
    protected string $translation = 'subcontract_wip_issue';
    protected function query(): SubcontractMaterialIssueQuery { return app(SubcontractWipIssueQuery::class); }
}

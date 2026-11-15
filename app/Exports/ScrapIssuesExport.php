<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\ScrapIssueTableQuery;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping};

class ScrapIssuesExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private int $companyId, private ?string $from, private ?string $to) {}
    public function query(): Builder { return app(ScrapIssueTableQuery::class)->builder($this->companyId, $this->from, $this->to); }
    public function headings(): array { return [__('ui.common.no'), __('ui.scrap_issue.number'), __('ui.scrap_issue.date'), __('ui.scrap_issue.customer'), __('ui.common.item_code'), __('ui.common.item_name'), __('ui.common.unit'), __('ui.common.quantity'), __('ui.common.description')]; }
    public function map($row): array { return [$row->id, $row->number, $row->issue_date?->format('Y-m-d'), $row->customer?->name, $row->scrapProduct?->code, $row->scrapProduct?->name, $row->scrapProduct?->unit?->symbol ?: $row->scrapProduct?->unit?->name, $row->quantity, $row->description]; }
}

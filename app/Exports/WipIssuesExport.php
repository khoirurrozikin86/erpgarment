<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\WipIssueTableQuery;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WipIssuesExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private int $companyId, private ?string $from, private ?string $to) {}
    public function query(): Builder { return app(WipIssueTableQuery::class)->builder($this->companyId, $this->from, $this->to); }
    public function headings(): array { return [__('ui.common.no'), __('ui.wip_issue.number'), __('ui.wip_issue.date'), __('ui.common.item_code'), __('ui.common.item_name'), __('ui.common.unit'), __('ui.wip_issue.quantity'), __('ui.common.description'), __('ui.common.recorded_by')]; }
    public function map($line): array { return [$line->id, $line->document?->number, $line->document?->transaction_date?->format('Y-m-d'), $line->product?->code, $line->product?->name, $line->product?->unit?->symbol ?: $line->product?->unit?->name, $line->quantity, $line->description, $line->document?->creator?->name]; }
}

<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\ScrapReceiptTableQuery;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ScrapReceiptsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private int $companyId, private ?string $from, private ?string $to) {}
    public function query(): Builder { return app(ScrapReceiptTableQuery::class)->builder($this->companyId, $this->from, $this->to); }
    public function headings(): array { return [__('ui.common.no'), __('ui.scrap_receipt.number'), __('ui.scrap_receipt.date'), __('ui.scrap_receipt.scrap_code'), __('ui.scrap_receipt.scrap_name'), __('ui.common.unit'), __('ui.common.quantity'), __('ui.scrap_receipt.source_code'), __('ui.scrap_receipt.source_name'), __('ui.scrap_receipt.production_number'), __('ui.common.description')]; }
    public function map($row): array { return [$row->id, $row->number, $row->receipt_date?->format('Y-m-d'), $row->scrapProduct?->code, $row->scrapProduct?->name, $row->scrapProduct?->unit?->symbol ?: $row->scrapProduct?->unit?->name, $row->quantity, $row->sourceProduct?->code, $row->sourceProduct?->name, $row->production_number, $row->description]; }
}

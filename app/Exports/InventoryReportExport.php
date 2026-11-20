<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\InventoryReportQuery;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class InventoryReportExport implements FromCollection, WithHeadings, WithStrictNullComparison, ShouldAutoSize
{
    public function __construct(private string $fromDate, private string $toDate) {}

    public function collection(): Collection
    {
        return app(InventoryReportQuery::class)->groups($this->fromDate, $this->toDate)
            ->flatMap(fn (array $group) => $group['details']->map(fn (array $sku, int $index) => [
                $group['code'], $sku['code'], $group['name'], $group['category'],
                $sku['color'], $sku['size'], $group['unit'],
                $sku['opening'], $sku['incoming'], $sku['outgoing'], $sku['closing'],
                $index === 0 ? $group['closing'] : null,
                $sku['threshold'],
            ]));
    }

    public function headings(): array
    {
        return [
            __('variants.stock_parent_code'), __('variants.stock_sku'), __('ui.common.item_name'),
            __('ui.common.category'), __('ui.workflow.color'), __('ui.workflow.size'), __('ui.common.unit'),
            __('ui.common.opening_balance'), __('ui.common.enter'), __('ui.common.exit'), __('ui.common.closing_balance'),
            __('variants.inventory_global_closing'), __('variants.inventory_minimum_balance'),
        ];
    }
}

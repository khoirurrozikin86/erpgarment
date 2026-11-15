<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\ScrapStockCardQuery;
use App\Models\StockMovement;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ScrapStockCardExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly int $productId) {}

    public function query()
    {
        return app(ScrapStockCardQuery::class)->movements($this->productId);
    }

    public function headings(): array
    {
        return [
            __('ui.common.date'),
            __('ui.workflow.document_number'),
            __('ui.common.description'),
            __('ui.common.quantity'),
            '(+/-)',
            __('ui.common.balance'),
            __('ui.common.recorded_by'),
        ];
    }

    public function map($movement): array
    {
        /** @var StockMovement $movement */
        $query = app(ScrapStockCardQuery::class);

        return [
            $query->transactionDate($movement),
            $query->documentNumber($movement),
            $query->description($movement),
            abs((float) $movement->quantity),
            (float) $movement->quantity > 0 ? '+' : ((float) $movement->quantity < 0 ? '-' : 'S'),
            (float) $movement->quantity_after,
            $movement->createdBy?->name ?: '—',
        ];
    }
}

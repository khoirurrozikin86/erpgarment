<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\MaterialIssueTableQuery;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MaterialIssuesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    private int $row = 0;
    public function __construct(private readonly int $companyId, private readonly ?string $fromDate = null, private readonly ?string $toDate = null) {}
    public function query(): Builder { return (new MaterialIssueTableQuery())->historyDocuments($this->companyId, $this->fromDate, $this->toDate); }
    public function headings(): array { return ['No.', 'No. MIS', 'Tanggal MIS', 'No. Permintaan Produksi', 'No. MO', 'Detail Barang']; }
    public function map($item): array
    {
        $mo = $item->items
            ->map(fn ($line) => $line->rawMaterialOrderItem?->sourceMaterial?->productionOrder?->number)
            ->filter()->unique()->implode(', ');
        $details = $item->items->map(function ($line) {
            $unit = $line->product?->unit?->symbol ?: $line->product?->unit?->name ?: '—';
            $color = $line->issued_color ?? '—';
            $size = $line->issued_size ?? '—';
            return ($line->product?->code ?: '—').' — '.($line->product?->name ?: '—').' / '.$color.' / '.$size.' / '.number_format((float) $line->quantity, 4, '.', '').' '.$unit;
        })->implode("\n");
        return [++$this->row, $item->number, $item->issue_date?->format('Y-m-d'), $item->rawMaterialOrder?->number ?: $item->request?->number ?: '—', $mo ?: $item->rawMaterialOrder?->production_order_number ?: $item->request?->productionOrder?->number ?: '—', $details];
    }
}

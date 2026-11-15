<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EquipmentStockCardExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $movements) {}

    public function collection(): Collection
    {
        return $this->movements;
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

    public function map($row): array
    {
        return [
            $row['date'] ?: '—',
            $row['number'],
            $row['description'],
            $row['amount'],
            $row['direction'],
            $row['balance'],
            $row['operator'],
        ];
    }
}

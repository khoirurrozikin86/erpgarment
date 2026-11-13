<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\LostItemTableQuery;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LostItemsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    private int $rowNumber = 0;

    public function query(): Builder
    {
        return (new LostItemTableQuery())->builder();
    }

    public function headings(): array
    {
        return ['No.', 'No. Berita Acara', 'Tanggal', 'Kode Item', 'Nama Item', 'Satuan', 'Qty', 'Keterangan'];
    }

    public function map($line): array
    {
        return [
            ++$this->rowNumber,
            $line->document?->number,
            $line->document?->document_date?->format('Y-m-d'),
            $line->product?->code,
            $line->product?->name,
            $line->product?->unit?->symbol ?: $line->product?->unit?->name,
            (float) $line->quantity,
            $line->description,
        ];
    }
}

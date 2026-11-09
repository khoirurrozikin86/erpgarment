<?php

namespace App\Exports;

use App\Models\Unit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UnitsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection()
    {
        return Unit::query()->orderBy('name')->get();
    }

    public function headings(): array
    {
        return ['Kode Satuan', 'Nama Satuan', 'Simbol', 'Deskripsi', 'Status'];
    }

    public function map($unit): array
    {
        return [
            $unit->code,
            $unit->name,
            $unit->symbol,
            $unit->description,
            $unit->is_active ? 'Active' : 'Not Active',
        ];
    }
}

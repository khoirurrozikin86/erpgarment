<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockOpnameTemplateExport implements FromArray, WithHeadings, WithColumnFormatting, ShouldAutoSize
{
    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        return ['Kode Barang / SKU', 'Qty Fisik'];
    }

    public function columnFormats(): array
    {
        return ['A' => '@', 'B' => '0.####'];
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithColumnFormatting
{
    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        return (new ProductsExport())->headings();
    }

    public function columnFormats(): array
    {
        return array_fill_keys(range('A', 'H'), '@');
    }
}

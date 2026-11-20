<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\{FromArray, WithHeadings};

class MaterialOrderLinesTemplate implements FromArray, WithHeadings
{
    public function headings(): array { return ['color', 'size', 'qty']; }
    public function array(): array { return [['BLACK', 'S', 2], ['BLACK', 'L', 4], ['BLACK', 'XL', 7], ['NAVY', 'S', 5]]; }
}

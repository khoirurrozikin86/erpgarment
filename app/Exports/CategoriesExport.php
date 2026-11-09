<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CategoriesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection()
    {
        return Category::query()->orderBy('name')->get();
    }

    public function headings(): array
    {
        return ['Kode Kategori', 'Nama Kategori', 'Deskripsi', 'Status'];
    }

    public function map($category): array
    {
        return [
            $category->code,
            $category->name,
            $category->description,
            $category->is_active ? 'Active' : 'Not Active',
        ];
    }
}

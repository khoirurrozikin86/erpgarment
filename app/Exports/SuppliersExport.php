<?php

namespace App\Exports;

use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SuppliersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection()
    {
        return Supplier::query()->orderBy('name')->get();
    }

    public function headings(): array
    {
        return ['Kode Supplier', 'Nama Supplier', 'Alamat', 'Contact Person', 'Telepon', 'Fax', 'Email', 'Negara'];
    }

    public function map($supplier): array
    {
        return [
            $supplier->code,
            $supplier->name,
            $supplier->address,
            $supplier->contact_person,
            $supplier->phone,
            $supplier->fax,
            $supplier->email,
            $supplier->country,
        ];
    }
}

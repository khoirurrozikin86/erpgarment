<?php

namespace App\Exports;

use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SuppliersExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize
{
    public function collection()
    {
        return Supplier::query()
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Kode Supplier',
            'Nama Supplier',
            'Jenis',
            'Contact Person',
            'Phone',
            'Email',
            'Website',
            'NPWP / Tax Number',
            'Alamat',
            'Kota',
            'Provinsi',
            'Kode Pos',
            'Payment Term',
            'Nama Bank',
            'Nomor Rekening',
            'Nama Rekening',
            'Catatan',
            'Status',
        ];
    }

    public function map($supplier): array
    {
        return [
            $supplier->code,
            $supplier->name,
            $supplier->supplier_type === 'company'
                ? 'Company'
                : 'Individual',
            $supplier->contact_person,
            $supplier->phone,
            $supplier->email,
            $supplier->website,
            $supplier->tax_number,
            $supplier->address,
            $supplier->city,
            $supplier->province,
            $supplier->postal_code,
            $supplier->payment_term,
            $supplier->bank_name,
            $supplier->bank_account_number,
            $supplier->bank_account_name,
            $supplier->notes,
            $supplier->is_active
                ? 'Active'
                : 'Not Active',
        ];
    }
}

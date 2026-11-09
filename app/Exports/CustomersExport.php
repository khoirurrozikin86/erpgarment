<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomersExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize
{
    public function collection()
    {
        return Customer::query()
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Kode Customer',
            'Nama Customer',
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
            'Credit Limit',
            'Catatan',
            'Status',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->code,
            $customer->name,
            $customer->customer_type === 'company'
                ? 'Company'
                : 'Individual',
            $customer->contact_person,
            $customer->phone,
            $customer->email,
            $customer->website,
            $customer->tax_number,
            $customer->address,
            $customer->city,
            $customer->province,
            $customer->postal_code,
            $customer->payment_term,
            $customer->credit_limit,
            $customer->notes,
            $customer->is_active
                ? 'Active'
                : 'Not Active',
        ];
    }
}

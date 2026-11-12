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
        return ['Kode Customer', 'Nama Customer', 'Alamat', 'Contact Person', 'Telepon', 'Fax', 'Email', 'Negara'];
    }

    public function map($customer): array
    {
        return [
            $customer->code, $customer->name, $customer->address, $customer->contact_person,
            $customer->phone, $customer->fax, $customer->email, $customer->country,
        ];
    }
}

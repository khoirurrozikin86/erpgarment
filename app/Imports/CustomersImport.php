<?php

namespace App\Imports;

use App\Domain\Customers\Services\CustomerService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CustomersImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function __construct(private CustomerService $service) {}

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $data = $row->toArray();
                $type = Str::lower(trim((string) ($data['jenis'] ?? 'company')));
                $status = Str::lower(trim((string) ($data['status'] ?? 'active')));

                $this->service->create([
                    'code' => trim((string) $data['kode_customer']),
                    'name' => trim((string) $data['nama_customer']),
                    'customer_type' => in_array($type, ['individual', 'perorangan'], true) ? 'individual' : 'company',
                    'contact_person' => $this->nullableString($data['contact_person'] ?? null),
                    'phone' => $this->nullableString($data['phone'] ?? null),
                    'email' => $this->nullableString($data['email'] ?? null),
                    'website' => $this->nullableString($data['website'] ?? null),
                    'tax_number' => $this->nullableString($data['npwp_tax_number'] ?? null),
                    'address' => $this->nullableString($data['alamat'] ?? null),
                    'city' => $this->nullableString($data['kota'] ?? null),
                    'province' => $this->nullableString($data['provinsi'] ?? null),
                    'postal_code' => $this->nullableString($data['kode_pos'] ?? null),
                    'payment_term' => $this->nullableString($data['payment_term'] ?? null) ?? 'COD',
                    'credit_limit' => $data['credit_limit'] ?? 0,
                    'notes' => $this->nullableString($data['catatan'] ?? null),
                    'is_active' => !in_array($status, ['0', 'false', 'no', 'tidak', 'not active', 'nonaktif'], true),
                ]);
            }
        });
    }

    public function rules(): array
    {
        return [
            '*.kode_customer' => ['required', 'string', 'max:50', 'distinct', 'unique:customers,code'],
            '*.nama_customer' => ['required', 'string', 'max:150'],
            '*.jenis' => ['required', 'in:Company,Individual,company,individual,Perusahaan,Perorangan'],
            '*.contact_person' => ['nullable', 'string', 'max:100'],
            '*.phone' => ['nullable', 'string', 'max:50'],
            '*.email' => ['nullable', 'email', 'max:150'],
            '*.website' => ['nullable', 'string', 'max:150'],
            '*.npwp_tax_number' => ['nullable', 'string', 'max:100'],
            '*.alamat' => ['nullable', 'string'],
            '*.kota' => ['nullable', 'string', 'max:100'],
            '*.provinsi' => ['nullable', 'string', 'max:100'],
            '*.kode_pos' => ['nullable', 'string', 'max:20'],
            '*.payment_term' => ['nullable', 'string', 'max:50'],
            '*.credit_limit' => ['nullable', 'numeric', 'min:0'],
            '*.catatan' => ['nullable', 'string'],
            '*.status' => ['nullable', 'in:Active,Not Active,active,inactive,1,0,Nonaktif'],
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}

<?php

namespace App\Imports;

use App\Domain\Suppliers\Services\SupplierService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SuppliersImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function __construct(private SupplierService $service) {}

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $data = $row->toArray();
                $this->service->create([
                    'code' => trim((string) ($data['kode_supplier'] ?? '')),
                    'name' => trim((string) ($data['nama_supplier'] ?? '')),
                    'address' => $this->nullableString($data['alamat'] ?? null),
                    'contact_person' => $this->nullableString($data['contact_person'] ?? null),
                    'phone' => $this->nullableString($data['telepon'] ?? null),
                    'fax' => $this->nullableString($data['fax'] ?? null),
                    'email' => $this->nullableString($data['email'] ?? null),
                    'country' => $this->nullableString($data['negara'] ?? null),
                    'is_active' => true,
                ]);
            }
        });
    }

    public function rules(): array
    {
        return [
            '*.kode_supplier' => ['required', 'string', 'max:50', 'distinct', 'unique:suppliers,code'],
            '*.nama_supplier' => ['required', 'string', 'max:150'],
            '*.alamat' => ['nullable', 'string'],
            '*.contact_person' => ['nullable', 'string', 'max:100'],
            '*.telepon' => ['nullable', 'string', 'max:50'],
            '*.fax' => ['nullable', 'string', 'max:50'],
            '*.email' => ['nullable', 'email', 'max:150'],
            '*.negara' => ['nullable', 'string', 'max:100'],
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}

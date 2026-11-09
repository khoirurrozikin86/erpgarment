<?php

namespace App\Imports;

use App\Domain\Categories\Services\CategoryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CategoriesImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function __construct(private CategoryService $service) {}

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $data = $row->toArray();
                $status = Str::lower(trim((string) ($data['status'] ?? 'active')));

                $this->service->create([
                    'code' => trim((string) $data['kode_kategori']),
                    'name' => trim((string) $data['nama_kategori']),
                    'description' => $this->nullableString($data['deskripsi'] ?? null),
                    'is_active' => !in_array($status, ['0', 'false', 'no', 'tidak', 'not active', 'nonaktif'], true),
                ]);
            }
        });
    }

    public function rules(): array
    {
        return [
            '*.kode_kategori' => ['required', 'string', 'max:50', 'distinct', 'unique:categories,code'],
            '*.nama_kategori' => ['required', 'string', 'max:100'],
            '*.deskripsi' => ['nullable', 'string'],
            '*.status' => ['nullable', 'in:Active,Not Active,active,inactive,1,0,Nonaktif'],
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}

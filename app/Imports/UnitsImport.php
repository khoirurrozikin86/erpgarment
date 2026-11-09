<?php

namespace App\Imports;

use App\Domain\Units\Services\UnitService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UnitsImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function __construct(private UnitService $service) {}

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $data = $row->toArray();
                $status = Str::lower(trim((string) ($data['status'] ?? 'active')));

                $this->service->create([
                    'code' => trim((string) $data['kode_satuan']),
                    'name' => trim((string) $data['nama_satuan']),
                    'symbol' => $this->nullableString($data['simbol'] ?? null),
                    'description' => $this->nullableString($data['deskripsi'] ?? null),
                    'is_active' => !in_array($status, ['0', 'false', 'no', 'tidak', 'not active', 'nonaktif'], true),
                ]);
            }
        });
    }

    public function rules(): array
    {
        return [
            '*.kode_satuan' => ['required', 'string', 'max:30', 'distinct', 'unique:units,code'],
            '*.nama_satuan' => ['required', 'string', 'max:100'],
            '*.simbol' => ['nullable', 'string', 'max:30'],
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

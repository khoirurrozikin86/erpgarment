<?php

namespace App\Imports;

use App\Domain\Products\Services\ProductService;
use App\Models\Category;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function __construct(private ProductService $service) {}

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                $data = $row->toArray();
                $categoryName = $this->nullableString($data['kategori'] ?? null);
                $unitName = $this->nullableString($data['satuan'] ?? null);
                $rowNumber = $index + 2;

                $category = $categoryName === null ? null : Category::query()
                    ->whereRaw('LOWER(name) = ?', [Str::lower($categoryName)])->first();
                $unitSearchName = $unitName === null ? null : preg_replace('/\s*\([^)]*\)\s*$/', '', $unitName);
                $unit = $unitSearchName === null ? null : Unit::query()
                    ->whereRaw('LOWER(name) = ?', [Str::lower($unitSearchName)])->first();

                if ($categoryName !== null && ! $category) {
                    throw new \RuntimeException("Kategori '{$categoryName}' pada baris {$rowNumber} tidak ditemukan.");
                }
                if ($unitName !== null && ! $unit) {
                    throw new \RuntimeException("Satuan '{$unitName}' pada baris {$rowNumber} tidak ditemukan.");
                }

                $this->service->create([
                    'code' => trim((string) ($data['kode_barang'] ?? '')),
                    'name' => trim((string) ($data['nama_barang'] ?? '')),
                    'category_id' => $category?->id,
                    'unit_id' => $unit?->id,
                    'product_type' => 'stock',
                    'garment_item_type' => 'general',
                    'style_code' => $this->nullableString($data['kode_style'] ?? null),
                    'color' => $this->nullableString($data['warna'] ?? null),
                    'size' => $this->nullableString($data['ukuran'] ?? null),
                    'is_active' => true,
                    'is_available' => $this->availableValue($data['available'] ?? null),
                ]);
            }
        });
    }

    public function rules(): array
    {
        return [
            '*.kategori' => ['nullable', 'string', 'max:100'],
            '*.kode_barang' => ['required', 'string', 'max:50', 'distinct', 'unique:products,code'],
            '*.nama_barang' => ['required', 'string', 'max:150'],
            '*.satuan' => ['nullable', 'string', 'max:50'],
            '*.warna' => ['nullable', 'string', 'max:80'],
            '*.ukuran' => ['nullable', 'string', 'max:40'],
            '*.available' => ['nullable', 'in:Available,Unavailable,Masih Ada,Tidak Ada,Masih ada,Tidak ada,available,unavailable,1,0,true,false,yes,no,ya,tidak'],
            '*.kode_style' => ['nullable', 'string', 'max:80', 'exists:styles,code'],
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private function availableValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || trim((string) $value) === '') {
            return true;
        }

        return in_array(Str::lower(trim((string) $value)), ['1', 'true', 'yes', 'ya', 'available', 'masih ada'], true);
    }
}

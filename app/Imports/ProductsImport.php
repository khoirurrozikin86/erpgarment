<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements ToCollection, WithHeadingRow, WithValidation
{
    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                $data = $row->toArray();
                $categoryName = trim((string) ($data['category_name'] ?? ''));
                $unitName = trim((string) ($data['unit_name'] ?? ''));
                $rowNumber = $index + 2;

                $category = $categoryName === '' ? null : Category::query()
                    ->whereRaw('LOWER(name) = ?', [Str::lower($categoryName)])->first();
                $unit = $unitName === '' ? null : Unit::query()
                    ->whereRaw('LOWER(name) = ?', [Str::lower($unitName)])->first();

                if ($categoryName !== '' && !$category) {
                    throw new \RuntimeException("Kategori '{$categoryName}' pada baris {$rowNumber} tidak ditemukan.");
                }
                if ($unitName !== '' && !$unit) {
                    throw new \RuntimeException("Satuan '{$unitName}' pada baris {$rowNumber} tidak ditemukan.");
                }

                Product::create([
                    'code' => trim((string) $data['code']),
                    'barcode' => $this->nullableString($data['barcode'] ?? null),
                    'sku' => $this->nullableString($data['sku'] ?? null),
                    'name' => trim((string) $data['name']),
                    'short_name' => $this->nullableString($data['short_name'] ?? null),
                    'category_id' => $category?->id,
                    'unit_id' => $unit?->id,
                    'product_type' => strtolower(trim((string) ($data['product_type'] ?? 'stock'))) ?: 'stock',
                    'purchase_price' => $data['purchase_price'] ?? 0,
                    'sales_price' => $data['sales_price'] ?? 0,
                    'min_stock' => $data['min_stock'] ?? 0,
                    'max_stock' => $data['max_stock'] ?? 0,
                    'reorder_point' => $data['reorder_point'] ?? 0,
                    'track_stock' => $this->booleanValue($data['track_stock'] ?? 1),
                    'taxable' => $this->booleanValue($data['taxable'] ?? 0),
                    'tax_rate' => $data['tax_rate'] ?? 0,
                    'allow_discount' => $this->booleanValue($data['allow_discount'] ?? 1),
                    'allow_purchase' => $this->booleanValue($data['allow_purchase'] ?? 1),
                    'allow_sales' => $this->booleanValue($data['allow_sales'] ?? 1),
                    'description' => $this->nullableString($data['description'] ?? null),
                    'is_active' => $this->booleanValue($data['is_active'] ?? 1),
                ]);
            }
        });
    }

    public function rules(): array
    {
        return [
            '*.code' => ['required', 'string', 'max:50', 'distinct', 'unique:products,code'],
            '*.barcode' => ['nullable', 'string', 'max:100', 'distinct', 'unique:products,barcode'],
            '*.sku' => ['nullable', 'string', 'max:100', 'distinct', 'unique:products,sku'],
            '*.name' => ['required', 'string', 'max:150'],
            '*.short_name' => ['nullable', 'string', 'max:100'],
            '*.category_name' => ['nullable', 'string'],
            '*.unit_name' => ['nullable', 'string'],
            '*.product_type' => ['nullable', 'in:stock,service'],
            '*.purchase_price' => ['nullable', 'numeric', 'min:0'],
            '*.sales_price' => ['nullable', 'numeric', 'min:0'],
            '*.min_stock' => ['nullable', 'numeric', 'min:0'],
            '*.max_stock' => ['nullable', 'numeric', 'min:0'],
            '*.reorder_point' => ['nullable', 'numeric', 'min:0'],
            '*.track_stock' => ['nullable', 'in:0,1'],
            '*.taxable' => ['nullable', 'in:0,1'],
            '*.tax_rate' => ['nullable', 'numeric', 'between:0,100'],
            '*.allow_discount' => ['nullable', 'in:0,1'],
            '*.allow_purchase' => ['nullable', 'in:0,1'],
            '*.allow_sales' => ['nullable', 'in:0,1'],
            '*.description' => ['nullable', 'string'],
            '*.is_active' => ['nullable', 'in:0,1'],
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private function booleanValue(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'ya', 'aktif'], true);
    }
}

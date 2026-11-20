<?php

namespace App\Imports;

use App\Domain\Products\Services\ProductService;
use App\Domain\Products\Services\ProductVariantService;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\VariantDimension;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function __construct(private ProductService $service) {}

    public function isEmptyWhen(array $row): bool
    {
        return !array_key_exists('kode_barang', $row)
            && (array_key_exists('product_unit_reference', $row) || array_key_exists('product_category_reference', $row));
    }

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            $unitsByLabel = Unit::query()->get(['id', 'code', 'name'])
                ->keyBy(fn (Unit $unit) => Str::lower($unit->importLabel()));

            $categoriesByLabel = Category::query()->get(['id', 'code', 'name'])
                ->keyBy(fn (Category $category) => Str::lower($category->importLabel()));

            $variants = [];

            foreach ($rows as $index => $row) {
                $data = $row->toArray();
                $rowNumber = $index + 2;
                $parentCode = $this->nullableString($data['kode_induk'] ?? null);
                $itemCode = $this->nullableString($data['kode_barang'] ?? null);
                $skuCode = $this->nullableString($data['kode_sku'] ?? null);
                $itemName = $this->nullableString($data['nama_barang'] ?? null);

                if ($parentCode !== null) {
                    if ($itemCode !== null) {
                        throw ValidationException::withMessages(['file' => __('variants.import_variant_item_code', ['row' => $rowNumber])]);
                    }

                    $variants[] = [$rowNumber, $parentCode, $skuCode, $data];
                    continue;
                }

                if ($itemCode === null || $itemName === null || $skuCode !== null) {
                    throw ValidationException::withMessages(['file' => __('variants.import_parent_fields', ['row' => $rowNumber])]);
                }

                $categoryName = $this->nullableString($data['kategori'] ?? null);
                $unitName = $this->nullableString($data['satuan'] ?? null);

                $category = $categoryName === null ? null : ($categoriesByLabel->get(Str::lower($categoryName))
                    ?? Category::query()->whereRaw('LOWER(name) = ?', [Str::lower($categoryName)])->first());
                $unit = null;
                if ($unitName !== null) {
                    $unit = $unitsByLabel->get(Str::lower($unitName))
                        ?? Unit::query()->whereRaw('LOWER(name) = ?', [Str::lower($unitName)])->first();

                    if (!$unit) {
                        $legacyUnitName = preg_replace('/\s*\([^)]*\)\s*$/', '', $unitName);
                        $unit = Unit::query()->whereRaw('LOWER(name) = ?', [Str::lower($legacyUnitName)])->first();
                    }
                }

                if ($categoryName !== null && ! $category) {
                    throw new \RuntimeException("Kategori '{$categoryName}' pada baris {$rowNumber} tidak ditemukan.");
                }
                if ($unitName !== null && ! $unit) {
                    throw new \RuntimeException("Satuan '{$unitName}' pada baris {$rowNumber} tidak ditemukan.");
                }

                $this->service->create([
                    'code' => $itemCode,
                    'name' => $itemName,
                    'category_id' => $category?->id,
                    'unit_id' => $unit?->id,
                    'product_type' => 'stock',
                    'garment_item_type' => 'general',
                    'color' => $this->nullableString($data['warna'] ?? null),
                    'size' => $this->nullableString($data['ukuran'] ?? null),
                    'is_active' => true,
                    'is_available' => $this->availableValue($data['available'] ?? null),
                ]);
            }

            $variantService = app(ProductVariantService::class);
            foreach ($variants as [$rowNumber, $parentCode, $skuCode, $data]) {
                $parent = Product::query()->where('code', $parentCode)->whereNull('parent_product_id')->first();
                if (!$parent) {
                    throw ValidationException::withMessages(['file' => __('variants.import_parent_missing', [
                        'row' => $rowNumber, 'code' => $parentCode,
                    ])]);
                }

                $color = $this->nullableString($data['warna'] ?? null);
                $size = $this->nullableString($data['ukuran'] ?? null);
                if ($color === null && $size === null) {
                    throw ValidationException::withMessages(['file' => __('variants.import_variant_dimensions', ['row' => $rowNumber])]);
                }
                $color = $color === null ? '' : VariantDimension::resolve('color', $color, 'warna', $rowNumber);
                $size = $size === null ? null : VariantDimension::resolve('size', $size, 'ukuran', $rowNumber);

                try {
                    $variantService->resolve($parent->id, $color, $size, $skuCode);
                } catch (ValidationException $exception) {
                    throw ValidationException::withMessages(['file' => __('variants.import_variant_error', [
                        'row' => $rowNumber, 'message' => $exception->validator->errors()->first(),
                    ])]);
                }
            }
        });
    }

    public function rules(): array
    {
        return [
            '*.kategori' => ['nullable', 'string', 'max:153'],
            '*.kode_barang' => ['nullable', 'string', 'max:50', 'distinct', 'unique:products,code'],
            '*.nama_barang' => ['nullable', 'string', 'max:150'],
            '*.kode_induk' => ['nullable', 'string', 'max:50'],
            '*.kode_sku' => ['nullable', 'string', 'max:50'],
            '*.satuan' => ['nullable', 'string', 'max:133'],
            '*.warna' => ['nullable', 'string', 'max:80'],
            '*.ukuran' => ['nullable', 'string', 'max:40'],
            '*.available' => ['nullable', 'in:Available,Unavailable,Masih Ada,Tidak Ada,Masih ada,Tidak ada,available,unavailable,1,0,true,false,yes,no,ya,tidak'],
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

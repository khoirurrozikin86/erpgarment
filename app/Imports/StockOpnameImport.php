<?php

namespace App\Imports;

use App\Domain\Inventory\Services\StockOpnameService;
use App\Models\Product;
use App\Models\StockOpname;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StockOpnameImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public ?StockOpname $document = null;

    public function __construct(
        private StockOpnameService $service,
        private int $userId,
        private ?string $notes,
    ) {}

    public function prepareForValidation(array $row, int $index): array
    {
        $row['kode_barang_sku'] = trim((string) ($row['kode_barang_sku'] ?? ''));

        return $row;
    }

    public function rules(): array
    {
        return [
            '*.kode_barang_sku' => ['required', 'string', 'max:50', 'distinct:ignore_case'],
            '*.qty_fisik' => ['required', 'numeric', 'min:0', 'max:999999999999.9999', 'decimal:0,4'],
        ];
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        if ($this->document !== null) {
            throw ValidationException::withMessages(['file' => __('stock_opname_import.one_sheet')]);
        }

        $this->document = DB::transaction(function () use ($rows) {
            $products = Product::query()->whereIn('code', $rows->pluck('kode_barang_sku')->all())
                ->withExists('variants')
                ->get()->keyBy(fn (Product $product) => Str::lower($product->code));
            $counts = [];
            foreach ($rows as $row) {
                $code = $row['kode_barang_sku'];
                $product = $products->get(Str::lower($code));
                if (!$product?->track_stock) {
                    throw ValidationException::withMessages(['file' => __('stock_opname_import.unknown_item', ['code' => $code])]);
                }
                if ($product->variants_exists) {
                    throw ValidationException::withMessages(['file' => __('stock_opname_import.use_sku', ['code' => $code])]);
                }
                if (isset($counts[$product->id])) {
                    throw ValidationException::withMessages(['file' => __('stock_opname_import.duplicate_item', ['code' => $code])]);
                }
                $counts[$product->id] = [
                    'product_id' => $product->id,
                    'counted_quantity' => $row['qty_fisik'],
                ];
            }

            $document = $this->service->create([
                'product_ids' => array_keys($counts),
                'notes' => $this->notes,
            ], $this->userId);

            return $this->service->saveCounts($document, ['items' => array_values($counts)], $this->userId);
        });
    }
}

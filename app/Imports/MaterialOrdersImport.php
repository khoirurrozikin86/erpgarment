<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\VariantDimension;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MaterialOrdersImport
{
    public function read(string $path): array
    {
        $book = IOFactory::load($path);
        try {
            $sheet = $book->getSheet(0);
            if ($sheet->getHighestDataRow() > 501) throw ValidationException::withMessages(['file' => __('variants.import_limit')]);
            $values = $sheet->toArray(null, false, false, false);
            $headers = array_map(fn ($v) => strtolower(trim((string) $v)), array_shift($values) ?? []);
            $rows = [];
            foreach ($values as $offset => $valuesRow) {
                if (!array_filter($valuesRow, fn ($v) => $v !== null && $v !== '')) continue;
                $row = [];
                foreach ($headers as $index => $key) if ($key !== '') $row[$key] = $valuesRow[$index] ?? null;
                foreach (['po_date', 'due_date'] as $key) {
                    if (is_numeric($row[$key] ?? null)) $row[$key] = Date::excelToDateTimeObject($row[$key])->format('Y-m-d');
                }
                $row['number'] = trim((string) ($row['number'] ?? ''));
                foreach (['style_code', 'color', 'size'] as $dimension) {
                    $row[$dimension] = trim((string) ($row[$dimension] ?? ''));
                }
                $row = validator($row, [
                    'number' => ['required', 'string', 'max:100'],
                    'buyer_po_number' => ['nullable', 'string', 'max:100'],
                    'no_cbd' => ['nullable', 'string', 'max:100'],
                    'currency' => ['required', 'string', 'max:10'],
                    'destination' => ['nullable', 'string', 'max:150'],
                    'po_date' => ['required', 'date_format:Y-m-d'],
                    'due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:po_date'],
                    'customer_code' => ['required', 'string'], 'style_code' => ['required', 'string', 'max:80'],
                    'color' => ['required', 'string', 'max:80'], 'size' => ['required', 'string', 'max:40'],
                    'qty' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
                    'price_per_unit' => ['nullable', 'numeric', 'min:0', 'max:999999999999.9999'],
                    'description' => ['nullable', 'string', 'max:5000'],
                ])->validate();
                foreach (['color', 'size'] as $kind) {
                    $row[$kind] = VariantDimension::resolve($kind, $row[$kind], 'file', $offset + 2);
                }
                $customer = Customer::where('code', trim($row['customer_code']))->where('is_active', true)->first();
                if (!$customer) throw ValidationException::withMessages(['file' => __('variants.import_master', ['row' => $offset + 2])]);
                $rows[] = $row + ['customer_id' => $customer->id, 'planned_quantity' => $row['qty'], 'excel_row' => $offset + 2];
            }
            if (!$rows) throw ValidationException::withMessages(['file' => __('variants.import_limit')]);
            foreach (collect($rows)->groupBy('number') as $group) {
                $headers = $group->map(fn ($row) => collect($row)->only(['buyer_po_number', 'no_cbd', 'currency', 'destination', 'po_date', 'due_date', 'customer_id'])->map(fn ($v) => (string) ($v ?? ''))->all());
                if ($headers->unique()->count() > 1) throw ValidationException::withMessages(['file' => __('variants.import_header_error')]);
            }
            $keys = collect($rows)->map(fn ($r) => mb_strtolower(trim($r['number']).'|'.$r['style_code'].'|'.trim($r['color']).'|'.trim($r['size'])));
            if ($keys->duplicates()->isNotEmpty()) throw ValidationException::withMessages(['file' => __('variants.duplicate_pair')]);
            return $rows;
        } finally {
            $book->disconnectWorksheets();
        }
    }
}

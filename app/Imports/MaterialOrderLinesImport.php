<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{ToCollection, WithHeadingRow, SkipsEmptyRows};

/** Read-only preview: saving the MO still uses its regular validation and actions. */
class MaterialOrderLinesImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public array $rows = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->rows[] = [
                'color' => trim((string) ($row['color'] ?? $row['warna'] ?? '')),
                'size' => trim((string) ($row['size'] ?? $row['ukuran'] ?? '')),
                'quantity' => $row['qty'] ?? $row['quantity'] ?? null,
            ];
        }
    }
}

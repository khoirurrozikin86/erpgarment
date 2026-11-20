<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\VariantDimension;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class MaterialOrdersTemplate implements FromArray, WithEvents, WithHeadings
{
    private Collection $colors;
    private Collection $sizes;

    public function __construct()
    {
        $this->colors = VariantDimension::options('color')->with('aliases')->get();
        $this->sizes = VariantDimension::options('size')->with('aliases')->get();
    }

    public function headings(): array
    {
        return ['number', 'buyer_po_number', 'no_cbd', 'currency', 'destination', 'po_date', 'due_date', 'customer_code', 'style_code', 'color', 'size', 'qty', 'price_per_unit', 'description'];
    }

    public function array(): array
    {
        $customer = Customer::where('is_active', true)->orderBy('id')->value('code') ?? 'CUSTOMER-CODE';
        $color = $this->colors->firstWhere('code', 'BLACK') ?? $this->colors->first();
        $sizes = $this->sizes->whereIn('code', ['S', 'L', 'XL'])->values();
        if ($sizes->isEmpty()) {
            $sizes = $this->sizes->take(3);
        }
        $date = now();

        return $sizes->map(fn ($size, $index) => [
            'MO-EXCEL-001', 'BUYER-PO-001', '', 'IDR', '', $date->toDateString(), $date->copy()->addDays(14)->toDateString(),
            $customer, 'STYLE-001', $color?->code, $size->code, [2, 4, 7][$index], 100000, 'Contoh / Example',
        ])->all();
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => [$this, 'addMasterLists']];
    }

    public function addMasterLists(AfterSheet $event): void
    {
        $sheet = $event->sheet->getDelegate();
        $book = $sheet->getParent();
        $sheet->setTitle('Production Order');
        $sheet->getStyle('J2:K501')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        foreach ([
            ['Master Warna', 'MO_COLORS', 'J', $this->colors],
            ['Master Ukuran', 'MO_SIZES', 'K', $this->sizes],
        ] as [$title, $rangeName, $column, $entries]) {
            $reference = $book->createSheet();
            $reference->setTitle($title);
            $reference->fromArray(['Kode / Code', 'Nama / Name', 'Alias'], null, 'A1');
            $reference->getStyle('A1:C1')->getFont()->setBold(true);

            foreach ($entries->values() as $index => $entry) {
                $row = $index + 2;
                $reference->setCellValueExplicit('A'.$row, $entry->code, DataType::TYPE_STRING);
                $reference->setCellValueExplicit('B'.$row, $entry->name, DataType::TYPE_STRING);
                $reference->setCellValueExplicit('C'.$row, $entry->aliases->pluck('code')->implode(', '), DataType::TYPE_STRING);
            }
            foreach (['A', 'B', 'C'] as $referenceColumn) {
                $reference->getColumnDimension($referenceColumn)->setAutoSize(true);
            }

            if ($entries->isNotEmpty()) {
                $book->addNamedRange(new NamedRange($rangeName, $reference, '$A$2:$A$'.($entries->count() + 1)));
                $validation = (new DataValidation())
                    ->setType(DataValidation::TYPE_LIST)
                    ->setFormula1($rangeName)
                    ->setShowDropDown(true)
                    ->setAllowBlank(true);
                $sheet->setDataValidation($column.'2:'.$column.'501', $validation);
            }
        }

        $book->setActiveSheetIndex(0);
    }
}

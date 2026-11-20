<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    public function __construct(private ?Collection $units = null, private ?Collection $categories = null) {}

    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        return [...(new ProductsExport())->headings(), 'Kode Induk', 'Kode SKU'];
    }

    public function columnFormats(): array
    {
        return array_fill_keys(range('A', 'I'), '@');
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => [$this, 'addReferenceLists']];
    }

    public function addReferenceLists(AfterSheet $event): void
    {
        $sheet = $event->sheet->getDelegate();

        foreach ([
            ['D', 'PRODUCT_UNITS', 'product_unit_reference', 'unit_reference_sheet', 'invalid_template_unit', $this->units],
            ['A', 'PRODUCT_CATEGORIES', 'product_category_reference', 'category_reference_sheet', 'invalid_template_category', $this->categories],
        ] as [$column, $range, $heading, $title, $error, $entries]) {
            if ($entries?->isNotEmpty()) {
                $this->addReferenceList($sheet, $entries, $column, $range, $heading, $title, $error);
            }
        }

        $sheet->getParent()->setActiveSheetIndex(0);
    }

    private function addReferenceList(
        Worksheet $sheet, Collection $entries, string $column, string $range,
        string $heading, string $title, string $error
    ): void {
        $book = $sheet->getParent();
        $reference = $book->createSheet()->setTitle(__('variants.'.$title));
        $reference->setCellValue('A1', $heading);
        $reference->getStyle('A1')->getFont()->setBold(true);
        $reference->getColumnDimension('A')->setAutoSize(true);

        foreach ($entries->values() as $index => $entry) {
            $row = $index + 2;
            $reference->setCellValueExplicit('A'.$row, $entry->importLabel(), DataType::TYPE_STRING);
        }

        $book->addNamedRange(new NamedRange($range, $reference, '$A$2:$A$'.($entries->count() + 1)));
        $validation = (new DataValidation())
            ->setType(DataValidation::TYPE_LIST)
            ->setFormula1($range)
            ->setShowDropDown(true)
            ->setShowErrorMessage(true)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setError(__('variants.'.$error))
            ->setAllowBlank(true);
        $sheet->setDataValidation($column.'2:'.$column.'1048576', $validation);
        $reference->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    }
}

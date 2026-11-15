<?php

namespace App\Exports;

use App\Domain\Manufacturing\Queries\ProductionResultReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class ProductionResultReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting
{
    public function __construct(private int $companyId, private array $filters) {}
    public function query() { return app(ProductionResultReportQuery::class)->builder($this->companyId, $this->filters); }
    public function headings(): array { return ['No Planning', 'PO Buyer', 'No Produksi', 'Tanggal', 'Kode Barang', 'Nama Barang', 'Satuan', 'Dari Sewing', 'Hasil Produksi', 'Gudang']; }
    public function map($row): array
    {
        $product = $row->productionOrder?->outputProduct;
        return [$row->productionOrder?->number, $row->productionOrder?->buyer_po_number, $row->packing_number, $row->processed_at?->format('d-m-Y'), $product?->code, $product?->name, $product?->unit?->symbol ?: $product?->unit?->name, (float) $row->input_quantity, (float) $row->good_quantity, '—'];
    }
    public function columnFormats(): array { return ['H' => '#,##0.####', 'I' => '#,##0.####']; }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

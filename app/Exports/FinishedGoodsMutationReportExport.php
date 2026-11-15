<?php

namespace App\Exports;

use App\Domain\Inventory\Queries\FinishedGoodsMutationReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class FinishedGoodsMutationReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting
{
    public function __construct(private array $filters) {}
    public function query() { return app(FinishedGoodsMutationReportQuery::class)->builder($this->filters); }
    public function headings(): array
    {
        return ['Kode Barang', 'Nama Barang', 'Satuan Barang', 'Jumlah Barang', 'Saldo Awal', 'Jumlah Pemasukan Barang', 'Jumlah Pengeluaran Barang', 'Penyesuaian (Adjustment)', 'Saldo Akhir', 'Hasil Pencacahan', 'Jumlah Selisih', 'Keterangan'];
    }
    public function map($product): array
    {
        $v = FinishedGoodsMutationReportQuery::values($product);
        return [$product->code, $product->name, $product->unit?->symbol ?: $product->unit?->name, null, $v['opening'], $v['incoming'], $v['outgoing'], $v['adjustment'], $v['closing'], $v['counted'], $v['difference'], $v['notes']];
    }
    public function columnFormats(): array { return array_fill_keys(range('D', 'K'), '#,##0.00'); }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

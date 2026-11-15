<?php

namespace App\Exports;

use App\Domain\Purchasing\Queries\OutgoingGoodsReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithCustomValueBinder};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class OutgoingGoodsReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder
{
    public function __construct(private int $companyId, private array $filters) {}
    public function query() { return app(OutgoingGoodsReportQuery::class)->builder($this->companyId, $this->filters); }
    public function headings(): array { return ['Jenis BC', 'No. Daftar', 'Tgl. Daftar', 'No Pengeluaran', 'Tanggal Pengeluaran', 'Penerima Barang', 'Kode HS', 'Kode Barang', 'Nama Barang', 'Satuan Barang', 'Jumlah Barang', 'Harga Satuan', 'Jumlah Harga']; }
    public function map($row): array
    {
        return [null, null, null, $row->number, $row->document_date, $row->recipient, null, $row->item_code, $row->item_name, $row->unit, (float) $row->quantity, null, null];
    }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

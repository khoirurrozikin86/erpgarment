<?php

namespace App\Exports;

use App\Domain\Purchasing\Queries\IncomingGoodsReportQuery;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

class IncomingGoodsReportExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder
{
    public function __construct(private array $filters) {}
    public function query() { return app(IncomingGoodsReportQuery::class)->builder($this->filters); }
    public function headings(): array { return ['Jenis BC', 'No. Daftar', 'Tgl. Daftar', 'No Penerimaan', 'Tanggal Penerimaan', 'Pengirim Barang', 'Kode HS', 'Kode Barang', 'Nama Barang', 'Satuan Barang', 'Jumlah Barang', 'Harga Satuan', 'Jumlah Harga', 'Mata Uang']; }
    public function map($row): array
    {
        $receipt = $row->goodsReceipt;
        return [$receipt?->customs_document_code, $receipt?->customs_document_number, $receipt?->customs_document_date?->format('d-m-Y'), $receipt?->number, $receipt?->received_at?->format('d-m-Y'), $receipt?->supplier?->name, '—', $row->product?->code, $row->product?->name, $row->product?->unit?->symbol ?: $row->product?->unit?->name, (float) $row->quantity, (float) $row->unit_price, (float) $row->line_total, $receipt?->currency];
    }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}

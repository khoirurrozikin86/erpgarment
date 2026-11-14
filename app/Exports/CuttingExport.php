<?php
namespace App\Exports;
use App\Domain\Manufacturing\Queries\CuttingTableQuery;
use Maatwebsite\Excel\Concerns\{FromQuery,WithHeadings,WithMapping};
class CuttingExport implements FromQuery,WithHeadings,WithMapping
{
 public function __construct(private int $companyId,private ?string $from,private ?string $to){}
 public function query(){return app(CuttingTableQuery::class)->builder($this->companyId,$this->from,$this->to);}
 public function headings():array{return ['No Cutting','Tanggal Cutting','No MO','PO Buyer','Style','Kode Produk','Nama Produk','Satuan','Qty Cutting','Qty Reject','Kirim ke Sewing','Kode Bahan','Nama Bahan','Satuan Bahan','Jumlah Waste','User Input'];}
 public function map($r):array{$p=$r->productionOrder?->outputProduct;$m=$r->materialProduct;return [$r->cutting_number,$r->processed_at?->format('Y-m-d'),$r->productionOrder?->number,$r->productionOrder?->buyer_po_number,$p?->style_code,$p?->code,$p?->name,$p?->unit?->symbol?:$p?->unit?->name,$r->input_quantity,$r->rejected_quantity,$r->good_quantity,$m?->code,$m?->name,$m?->unit?->symbol?:$m?->unit?->name,$r->material_waste_quantity,$r->processor?->username?:$r->processor?->name];}
}

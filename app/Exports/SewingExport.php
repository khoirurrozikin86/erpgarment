<?php
namespace App\Exports;
use App\Domain\Manufacturing\Queries\SewingTableQuery;
use Maatwebsite\Excel\Concerns\{FromQuery,WithHeadings,WithMapping};
class SewingExport implements FromQuery,WithHeadings,WithMapping
{
 public function __construct(private int $companyId,private ?string $from,private ?string $to){}
 public function query(){return app(SewingTableQuery::class)->builder($this->companyId,$this->from,$this->to);}
 public function headings():array{return ['No Sewing','Tanggal Sewing','No MO','PO Buyer','Style','Kode Produk','Nama Produk','Satuan','Cutting Reception','Qty Sewing','Jumlah Reject','Kirim ke Packing','Kode Bahan','Nama Bahan','Satuan Bahan','Jumlah Waste','User Input'];}
 public function map($r):array{$p=$r->productionOrder?->outputProduct;$m=$r->materialProduct;$cutting=$r->productionOrder?->stageEntries->sum('good_quantity');return [$r->sewing_number,$r->processed_at?->format('Y-m-d'),$r->productionOrder?->number,$r->productionOrder?->buyer_po_number,$p?->style_code,$p?->code,$p?->name,$p?->unit?->symbol?:$p?->unit?->name,$cutting,$r->input_quantity,$r->rejected_quantity,$r->good_quantity,$m?->code,$m?->name,$m?->unit?->symbol?:$m?->unit?->name,$r->material_waste_quantity,$r->processor?->username?:$r->processor?->name];}
}

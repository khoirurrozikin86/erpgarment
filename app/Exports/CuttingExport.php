<?php
namespace App\Exports;
use App\Domain\Manufacturing\Queries\CuttingTableQuery;
use Maatwebsite\Excel\Concerns\{FromQuery,WithHeadings,WithMapping};
class CuttingExport implements FromQuery,WithHeadings,WithMapping
{
 public function __construct(private int $companyId,private ?string $from,private ?string $to){}
 public function query(){return app(CuttingTableQuery::class)->builder($this->companyId,$this->from,$this->to);}
 public function headings():array{return ['No Cutting','Tanggal Cutting','No MO','PO Buyer','Style','Kode Produk','Nama Produk','Color','Size','Satuan','Qty Cutting (PCS)','Qty Reject (PCS)','Kirim ke Sewing (PCS)','Kode Bahan','Nama Bahan','Satuan Bahan','Qty Bahan Digunakan','Jumlah Waste','User Input'];}
 public function map($r):array{$p=$r->productionOrder?->outputProduct;$m=$r->materialProduct;return [$r->cutting_number,$r->processed_at?->format('Y-m-d'),$r->productionOrder?->number,$r->productionOrder?->buyer_po_number,$r->productionOrder?->model_style,$r->productionOrder?->stageProductCode('cutting'),$p?->name,$p?->color,$p?->size,'PCS',(float)$r->good_quantity+(float)$r->rejected_quantity,$r->rejected_quantity,$r->good_quantity,$m?->code,$m?->name,$m?->unit?->symbol?:$m?->unit?->name,$r->input_quantity,$r->material_waste_quantity,$r->processor?->username?:$r->processor?->name];}
}

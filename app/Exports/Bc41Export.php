<?php
namespace App\Exports;
use App\Domain\Customs\Queries\Bc41Query;
use Maatwebsite\Excel\Concerns\{FromQuery,WithHeadings,WithMapping,WithCustomValueBinder,WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Cell\{Cell,DefaultValueBinder,DataType};
class Bc41Export extends DefaultValueBinder implements FromQuery,WithHeadings,WithMapping,WithCustomValueBinder,WithColumnFormatting {
 public function __construct(private int $companyId,private array $filters){}
 public function query(){return app(Bc41Query::class)->builder($this->companyId,$this->filters);}
 public function headings():array{return array_map(fn($k)=>__(in_array($k, ['parent_code', 'sku_code', 'color', 'size'], true) ? 'bc23.'.$k : 'bc41.'.$k),['submission_number','document_number','document_date','shipment_number','shipment_date','customer','parent_code','sku_code','name','color','size','unit','quantity','unit_price','total_price','hs_code','currency','tax_invoice_number','tax_invoice_date']);}
 public function map($r):array{return [$r->submission_number,$r->document_number,$r->document_date,$r->shipment_number,$r->shipment_date,$r->customer_name,$r->parent_code,$r->sku_code,$r->item_name,$r->color,$r->size,$r->unit,$r->quantity===null?null:(float)$r->quantity,$r->unit_price===null?null:(float)$r->unit_price,$r->total_price===null?null:(float)$r->total_price,$r->hs_code,$r->currency,$r->tax_invoice_number,$r->tax_invoice_date];}
 public function columnFormats():array{return array_fill_keys(['M','N','O'],'#,##0.0000');}
 public function bindValue(Cell $cell,$value){if(is_string($value)){$cell->setValueExplicit($value,DataType::TYPE_STRING);return true;}return parent::bindValue($cell,$value);}
}

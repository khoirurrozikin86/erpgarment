<?php
namespace App\Exports;
use App\Domain\Customs\Queries\Bc27Query;
use Maatwebsite\Excel\Concerns\{FromQuery,WithHeadings,WithMapping,WithCustomValueBinder,WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Cell\{Cell,DefaultValueBinder,DataType};
class Bc27Export extends DefaultValueBinder implements FromQuery,WithHeadings,WithMapping,WithCustomValueBinder,WithColumnFormatting {
 public function __construct(private int $companyId,private array $filters){}
 public function query(){return app(Bc27Query::class)->builder($this->companyId,$this->filters);}
 public function headings():array{return array_map(fn($k)=>__(in_array($k, ['parent_code', 'sku_code', 'color', 'size'], true) ? 'bc23.'.$k : 'bc27.'.$k),['submission_number','document_number','document_date','receipt_number','receipt_date','supplier','parent_code','sku_code','name','color','size','unit','quantity','unit_price','total_price','cif','country_code','hs_code','currency','invoice_number','invoice_date']);}
 public function map($r):array{return [$r->submission_number,$r->document_number,$r->document_date,$r->receipt_number,$r->receipt_date,$r->supplier_name,$r->parent_code,$r->sku_code,$r->item_name,$r->color,$r->size,$r->unit,$r->quantity===null?null:(float)$r->quantity,$r->unit_price===null?null:(float)$r->unit_price,$r->total_price===null?null:(float)$r->total_price,$r->cif===null?null:(float)$r->cif,$r->country_code,$r->hs_code,$r->currency,$r->invoice_number,$r->invoice_date];}
 public function columnFormats():array{return array_fill_keys(['M','N','O','P'],'#,##0.0000');}
 public function bindValue(Cell $cell,$value){if(is_string($value)){$cell->setValueExplicit($value,DataType::TYPE_STRING);return true;}return parent::bindValue($cell,$value);}
}

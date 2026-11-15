<?php
namespace App\Exports;

use App\Domain\Subcontract\Queries\SubcontractMaterialIssueQuery;
use Maatwebsite\Excel\Concerns\{FromQuery,WithHeadings,WithMapping,WithCustomValueBinder,WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Cell\{Cell,DefaultValueBinder,DataType};

class SubcontractMaterialIssueExport extends DefaultValueBinder implements FromQuery,WithHeadings,WithMapping,WithCustomValueBinder,WithColumnFormatting
{
    public function __construct(private int $companyId,private array $filters,private string $issueKind='raw'){}
    private function translation(): string { return match($this->issueKind){'wip'=>'subcontract_wip_issue','receipt'=>'subcontract_receipt',default=>'subcontract_material_issue'}; }
    public function query(){return app(match($this->issueKind){'wip'=>\App\Domain\Subcontract\Queries\SubcontractWipIssueQuery::class,'receipt'=>\App\Domain\Subcontract\Queries\SubcontractReceiptQuery::class,default=>SubcontractMaterialIssueQuery::class})->builder($this->companyId,$this->filters);}
    public function headings():array{return array_map(fn($k)=>__($this->translation().'.'.$k),['number','date','receiver','contract','style','code','name','unit','quantity','description',...($this->issueKind==='receipt'?['invoice_number','invoice_date']:['vehicle']),'status','confirmed_by','confirmed_at']);}
    public function map($r):array{return [$r->document?->number,$r->document?->issue_date?->format('Y-m-d'),$r->document?->receiver?->name,($this->issueKind==='receipt'?($r->document?->contract_number??$r->document?->contract?->contract_number):$r->document?->contract?->contract_number),$r->style,$r->product?->code,$r->product?->name,$r->product?->unit?->symbol?:$r->product?->unit?->name,(float)$r->quantity,$r->description,...($this->issueKind==='receipt'?[$r->document?->invoice_number,$r->document?->invoice_date?->format('Y-m-d')]:[$r->document?->vehicle_number]),__($this->translation().'.'.$r->document->status),$r->document?->confirmer?->name,$r->document?->confirmed_at?->format('Y-m-d H:i:s')];}
    public function columnFormats():array{return ['I'=>'#,##0.0000'];}
    public function bindValue(Cell $cell,$value){if(is_string($value)){$cell->setValueExplicit($value,DataType::TYPE_STRING);return true;}return parent::bindValue($cell,$value);}
}

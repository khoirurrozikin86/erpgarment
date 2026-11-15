<?php
namespace App\Domain\Customs\Queries;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
class Bc262Query {
 public function builder(int $companyId,array $f):Builder {
  return DB::table('customs_documents as d')->leftJoin('customs_document_items as i','i.document_id','=','d.id')->leftJoin('suppliers as s','s.id','=','d.supplier_id')
   ->leftJoin('subcontractors as sub','sub.id','=','d.subcontractor_id')->where('d.company_id',$companyId)->where('d.document_type','BC 2.6.2')->whereNull('d.deleted_at')->where('d.document_date','>=',$f['from_date'])->where('d.document_date','<=',$f['to_date'])
   ->select(['d.id as document_id','d.submission_number','d.document_number','d.document_date','d.currency','d.invoice_number','d.invoice_date','i.receipt_number','i.receipt_date','i.item_code','i.item_name','i.unit','i.quantity','i.unit_price','i.total_price','i.cif','i.country_code','i.hs_code'])
   ->selectRaw('COALESCE(s.name, sub.name) as supplier_name')->when($f['search']??null,function($q,$search){$q->where(function($q)use($search){foreach(['i.item_code','i.item_name','i.receipt_number','d.submission_number','d.document_number','s.name','sub.name'] as $column)$q->orWhere($column,'like',"%{$search}%");});})->orderByDesc('d.id')->orderBy('i.id');
 }
}

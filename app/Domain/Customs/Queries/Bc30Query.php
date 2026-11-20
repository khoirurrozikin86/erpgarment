<?php
namespace App\Domain\Customs\Queries;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
class Bc30Query {
 public function builder(int $companyId,array $f):Builder {
  $documents = DB::table('customs_documents as d')->leftJoin('customs_document_items as i','i.document_id','=','d.id')->leftJoin('customers as s','s.id','=','d.customer_id')
   ->where('d.company_id',$companyId)->where('d.document_type','BC 3.0')->whereNull('d.deleted_at')->where('d.document_date','>=',$f['from_date'])->where('d.document_date','<=',$f['to_date'])
   ->select(['d.id as document_id','d.submission_number','d.document_number','d.document_date','d.currency','s.name as customer_name','i.shipment_number','i.shipment_date','i.item_code','i.item_name','i.unit','i.quantity','i.unit_price','i.total_price','i.hs_code']);

  return BcDocumentVariantQuery::rows($documents, $f['search'] ?? null, 'shipment_number', 'customer_name');
 }
}

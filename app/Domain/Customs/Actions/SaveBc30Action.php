<?php
namespace App\Domain\Customs\Actions;
use App\Domain\Audit\Services\AuditLogService;
use App\Models\{CustomsDocument,Product,GoodsSentNoteItem};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class SaveBc30Action {
 public function save(?CustomsDocument $document,array $data,int $companyId,int $userId,bool $saveItems):CustomsDocument {
  return DB::transaction(function()use($document,$data,$companyId,$userId,$saveItems){
   $before=null;$action='CREATE';if($document){$document=CustomsDocument::where('company_id',$companyId)->where('document_type','BC 3.0')->lockForUpdate()->findOrFail($document->id);$before=$document->load('items')->toArray();$action='UPDATE';if(!$saveItems&&(int)$document->customer_id!==(int)$data['customer_id']&&$document->items()->exists())throw ValidationException::withMessages(['customer_id'=>__('bc30.customer_change')]);}
   $items=$data['items']??[];unset($data['items'],$data['intent']);
   if(!$document){$document=CustomsDocument::create($data+['company_id'=>$companyId,'document_type'=>'BC 3.0','created_by'=>$userId]);}else $document->update($data);
   if($saveItems){
    $prepared=[];
    foreach($items as $line){
     $product=Product::with('unit')->where('is_active',true)->findOrFail($line['product_id']);
     if(!empty($line['goods_sent_note_item_id'])){
      $source=GoodsSentNoteItem::with('note')->where('product_id',$product->id)->where('customer_id',$data['customer_id'])->whereHas('note',fn($q)=>$q->where('company_id',$companyId)->whereNotNull('approved_at'))->lockForUpdate()->findOrFail($line['goods_sent_note_item_id']);$line['shipment_number']=$source->note->number;$line['shipment_date']=$source->note->sent_date->toDateString();
     }
     $line['item_code']=$product->code;$line['item_name']=$product->name;$line['unit']=$product->unit?->symbol?:$product->unit?->name;
     $line['total_price']=round((float)$line['quantity']*(float)$line['unit_price'],4);if($line['total_price']>999999999999999.9999)throw ValidationException::withMessages(['items'=>__('bc30.amount_overflow')]);
     $prepared[]=$line;
    }
    $document->items()->delete();$document->items()->createMany($prepared);
   }
   app(AuditLogService::class)->log($action,'BC30',"{$action} BC 3.0 {$document->submission_number}",$document,$before,$document->fresh()->load('items')->toArray(),$userId);return $document;
  });
 }
 public function delete(CustomsDocument $document,int $companyId,int $userId):void {
  DB::transaction(function()use($document,$companyId,$userId){$document=CustomsDocument::where('company_id',$companyId)->where('document_type','BC 3.0')->lockForUpdate()->findOrFail($document->id);$before=$document->load('items')->toArray();$document->delete();app(AuditLogService::class)->log('DELETE','BC30',"Deleted BC 3.0 {$document->submission_number}",$document,$before,null,$userId);});
 }
}

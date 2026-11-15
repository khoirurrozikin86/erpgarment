<?php
namespace App\Domain\Customs\Actions;
use App\Domain\Audit\Services\AuditLogService;
use App\Models\{CustomsDocument,Product,GoodsSentNoteItem,GoodsReceiptItem,EquipmentReceiptItem};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class SaveBc41Action {
 public function save(?CustomsDocument $document,array $data,int $companyId,int $userId,bool $saveItems):CustomsDocument {
  return DB::transaction(function()use($document,$data,$companyId,$userId,$saveItems){
   $before=null;$action='CREATE';if($document){$document=CustomsDocument::where('company_id',$companyId)->where('document_type','BC 4.1')->lockForUpdate()->findOrFail($document->id);$before=$document->load('items')->toArray();$action='UPDATE';if(!$saveItems&&(int)$document->customer_id!==(int)$data['customer_id']&&$document->items()->exists())throw ValidationException::withMessages(['customer_id'=>__('bc41.customer_change')]);}
   $items=$data['items']??[];unset($data['items'],$data['intent']);
   if(!$document){$document=CustomsDocument::create($data+['company_id'=>$companyId,'document_type'=>'BC 4.1','created_by'=>$userId]);}else $document->update($data);
   if($saveItems){
    $prepared=[];
    foreach($items as $line){
     $product=Product::with('unit')->where('is_active',true)->findOrFail($line['product_id']);
     if(count(array_filter([$line['goods_sent_note_item_id']??null,$line['goods_receipt_item_id']??null,$line['equipment_receipt_item_id']??null]))>1)throw ValidationException::withMessages(['items'=>__('bc41.invalid_source')]);
     if(!empty($line['goods_receipt_item_id'])){$source=GoodsReceiptItem::with('goodsReceipt')->where('product_id',$product->id)->whereHas('goodsReceipt',fn($q)=>$q->where('status','approved'))->lockForUpdate()->findOrFail($line['goods_receipt_item_id']);$line['receipt_number']=$source->goodsReceipt->number;$line['receipt_date']=$source->goodsReceipt->received_at->toDateString();}
     if(!empty($line['equipment_receipt_item_id'])){$source=EquipmentReceiptItem::with('document')->where('product_id',$product->id)->whereHas('document',fn($q)=>$q->where('company_id',$companyId))->lockForUpdate()->findOrFail($line['equipment_receipt_item_id']);$line['receipt_number']=$source->document->number;$line['receipt_date']=$source->document->receipt_date->toDateString();}
     if(!empty($line['goods_sent_note_item_id'])){
      $source=GoodsSentNoteItem::with('note')->where('product_id',$product->id)->where('customer_id',$data['customer_id'])->whereHas('note',fn($q)=>$q->where('company_id',$companyId)->whereNotNull('approved_at'))->lockForUpdate()->findOrFail($line['goods_sent_note_item_id']);$line['shipment_number']=$source->note->number;$line['shipment_date']=$source->note->sent_date->toDateString();
     }
     $line['item_code']=$product->code;$line['item_name']=$product->name;$line['unit']=$product->unit?->symbol?:$product->unit?->name;
     $line['total_price']=round((float)$line['quantity']*(float)$line['unit_price'],4);if($line['total_price']>999999999999999.9999)throw ValidationException::withMessages(['items'=>__('bc41.amount_overflow')]);
     $prepared[]=$line;
    }
    $document->items()->delete();$document->items()->createMany($prepared);
   }
   app(AuditLogService::class)->log($action,'BC41',"{$action} BC 4.1 {$document->submission_number}",$document,$before,$document->fresh()->load('items')->toArray(),$userId);return $document;
  });
 }
 public function delete(CustomsDocument $document,int $companyId,int $userId):void {
  DB::transaction(function()use($document,$companyId,$userId){$document=CustomsDocument::where('company_id',$companyId)->where('document_type','BC 4.1')->lockForUpdate()->findOrFail($document->id);$before=$document->load('items')->toArray();$document->delete();app(AuditLogService::class)->log('DELETE','BC41',"Deleted BC 4.1 {$document->submission_number}",$document,$before,null,$userId);});
 }
}

<?php
namespace App\Domain\Customs\Actions;
use App\Domain\Audit\Services\AuditLogService;
use App\Models\{CustomsDocument,Product,GoodsReceiptItem,EquipmentReceiptItem};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class SaveBc20Action {
 public function save(?CustomsDocument $document,array $data,int $companyId,int $userId,bool $saveItems):CustomsDocument {
  return DB::transaction(function()use($document,$data,$companyId,$userId,$saveItems){
   $before=null;$action='CREATE';if($document){$document=CustomsDocument::where('company_id',$companyId)->where('document_type','BC 2.0')->lockForUpdate()->findOrFail($document->id);$before=$document->load('items')->toArray();$action='UPDATE';if(!$saveItems&&(int)$document->supplier_id!==(int)$data['supplier_id']&&$document->items()->exists())throw ValidationException::withMessages(['supplier_id'=>__('bc20.supplier_change')]);}
   $items=$data['items']??[];unset($data['items'],$data['intent']);
   if(!$document){$document=CustomsDocument::create($data+['company_id'=>$companyId,'document_type'=>'BC 2.0','created_by'=>$userId]);}else $document->update($data);
   if($saveItems){
    $prepared=[];
    foreach($items as $line){
     $product=Product::with('unit')->where('is_active',true)->findOrFail($line['product_id']);
     if(!empty($line['goods_receipt_item_id'])&&!empty($line['equipment_receipt_item_id']))throw ValidationException::withMessages(['items'=>__('bc20.invalid_source')]);
     if(!empty($line['goods_receipt_item_id'])){
      $source=GoodsReceiptItem::with('goodsReceipt')->where('product_id',$product->id)->whereHas('goodsReceipt',fn($q)=>$q->where('supplier_id',$data['supplier_id'])->where('status','approved'))->lockForUpdate()->findOrFail($line['goods_receipt_item_id']);$line['receipt_number']=$source->goodsReceipt->number;$line['receipt_date']=$source->goodsReceipt->received_at->toDateString();
     }
     if(!empty($line['equipment_receipt_item_id'])){
      $source=EquipmentReceiptItem::with('document')->where('product_id',$product->id)->whereHas('document',fn($q)=>$q->where('company_id',$companyId)->where('supplier_id',$data['supplier_id']))->lockForUpdate()->findOrFail($line['equipment_receipt_item_id']);$line['receipt_number']=$source->document->number;$line['receipt_date']=$source->document->receipt_date->toDateString();
     }
     $line['item_code']=$product->code;$line['item_name']=$product->name;$line['unit']=$product->unit?->symbol?:$product->unit?->name;
     $line['total_price']=round((float)$line['quantity']*(float)$line['unit_price'],4);if($line['total_price']>999999999999999.9999)throw ValidationException::withMessages(['items'=>__('bc20.amount_overflow')]);
     $prepared[]=$line;
    }
    $document->items()->delete();$document->items()->createMany($prepared);
   }
   app(AuditLogService::class)->log($action,'BC20',"{$action} BC 2.0 {$document->submission_number}",$document,$before,$document->fresh()->load('items')->toArray(),$userId);return $document;
  });
 }
 public function delete(CustomsDocument $document,int $companyId,int $userId):void {
  DB::transaction(function()use($document,$companyId,$userId){$document=CustomsDocument::where('company_id',$companyId)->where('document_type','BC 2.0')->lockForUpdate()->findOrFail($document->id);$before=$document->load('items')->toArray();$document->delete();app(AuditLogService::class)->log('DELETE','BC20',"Deleted BC 2.0 {$document->submission_number}",$document,$before,null,$userId);});
 }
}

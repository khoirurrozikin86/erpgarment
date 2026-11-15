<?php
namespace App\Domain\Customs\Actions;
use App\Domain\Audit\Services\AuditLogService;
use App\Models\{CustomsDocument,Product,GoodsReceiptItem,EquipmentReceiptItem,SubcontractMaterialIssueItem};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class SaveBc262Action {
 public function save(?CustomsDocument $document,array $data,int $companyId,int $userId,bool $saveItems):CustomsDocument {
  return DB::transaction(function()use($document,$data,$companyId,$userId,$saveItems){
   $before=null;$action='CREATE';if($document){$document=CustomsDocument::where('company_id',$companyId)->where('document_type','BC 2.6.2')->lockForUpdate()->findOrFail($document->id);$before=$document->load('items')->toArray();$action='UPDATE';if(!$saveItems&&((int)$document->supplier_id!==(int)$data['supplier_id']||(int)$document->subcontractor_id!==(int)$data['subcontractor_id'])&&$document->items()->exists())throw ValidationException::withMessages(['supplier_id'=>__('bc262.supplier_change')]);}
   $items=$data['items']??[];unset($data['items'],$data['intent']);
   if(!$document){$document=CustomsDocument::create($data+['company_id'=>$companyId,'document_type'=>'BC 2.6.2','created_by'=>$userId]);}else $document->update($data);
   if($saveItems){
    $prepared=[];
    foreach($items as $line){
     $product=Product::with('unit')->where('is_active',true)->findOrFail($line['product_id']);
     if(count(array_filter([$line['goods_receipt_item_id']??null,$line['equipment_receipt_item_id']??null,$line['subcontract_receipt_item_id']??null]))>1)throw ValidationException::withMessages(['items'=>__('bc262.invalid_source')]);
     if(!empty($line['goods_receipt_item_id'])){if(empty($data['supplier_id']))throw ValidationException::withMessages(['items'=>__('bc262.invalid_source')]);
      $source=GoodsReceiptItem::with('goodsReceipt')->where('product_id',$product->id)->whereHas('goodsReceipt',fn($q)=>$q->where('supplier_id',$data['supplier_id'])->where('status','approved'))->lockForUpdate()->findOrFail($line['goods_receipt_item_id']);$line['receipt_number']=$source->goodsReceipt->number;$line['receipt_date']=$source->goodsReceipt->received_at->toDateString();
     }
     if(!empty($line['equipment_receipt_item_id'])){if(empty($data['supplier_id']))throw ValidationException::withMessages(['items'=>__('bc262.invalid_source')]);
      $source=EquipmentReceiptItem::with('document')->where('product_id',$product->id)->whereHas('document',fn($q)=>$q->where('company_id',$companyId)->where('supplier_id',$data['supplier_id']))->lockForUpdate()->findOrFail($line['equipment_receipt_item_id']);$line['receipt_number']=$source->document->number;$line['receipt_date']=$source->document->receipt_date->toDateString();
     }
     if(!empty($line['subcontract_receipt_item_id'])){if(empty($data['subcontractor_id']))throw ValidationException::withMessages(['items'=>__('bc262.invalid_source')]);$source=SubcontractMaterialIssueItem::with('document')->where('product_id',$product->id)->whereHas('document',fn($q)=>$q->where('company_id',$companyId)->where('subcontractor_id',$data['subcontractor_id'])->where('issue_kind','receipt')->where('status','confirmed'))->lockForUpdate()->findOrFail($line['subcontract_receipt_item_id']);$line['receipt_number']=$source->document->number;$line['receipt_date']=$source->document->issue_date->toDateString();}
     $line['item_code']=$product->code;$line['item_name']=$product->name;$line['unit']=$product->unit?->symbol?:$product->unit?->name;
     $line['total_price']=round((float)$line['quantity']*(float)$line['unit_price'],4);if($line['total_price']>999999999999999.9999)throw ValidationException::withMessages(['items'=>__('bc262.amount_overflow')]);
     $prepared[]=$line;
    }
    $document->items()->delete();$document->items()->createMany($prepared);
   }
   app(AuditLogService::class)->log($action,'BC262',"{$action} BC 2.6.2 {$document->submission_number}",$document,$before,$document->fresh()->load('items')->toArray(),$userId);return $document;
  });
 }
 public function delete(CustomsDocument $document,int $companyId,int $userId):void {
  DB::transaction(function()use($document,$companyId,$userId){$document=CustomsDocument::where('company_id',$companyId)->where('document_type','BC 2.6.2')->lockForUpdate()->findOrFail($document->id);$before=$document->load('items')->toArray();$document->delete();app(AuditLogService::class)->log('DELETE','BC262',"Deleted BC 2.6.2 {$document->submission_number}",$document,$before,null,$userId);});
 }
}

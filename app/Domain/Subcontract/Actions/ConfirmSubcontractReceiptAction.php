<?php
namespace App\Domain\Subcontract\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\{InventoryLot,Product,ProductStock,StockMovement,SubcontractMaterialIssueDocument};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmSubcontractReceiptAction
{
    public function __invoke(SubcontractMaterialIssueDocument $document,int $companyId,int $userId): void
    {
        DB::transaction(function()use($document,$companyId,$userId){
            $document=SubcontractMaterialIssueDocument::where('company_id',$companyId)->where('issue_kind','receipt')->lockForUpdate()->findOrFail($document->id);
            if($document->status!=='draft')throw ValidationException::withMessages(['document'=>__('subcontract_receipt.already_confirmed')]);
            $items=$document->items()->orderBy('product_id')->orderBy('id')->lockForUpdate()->get();
            if($items->isEmpty())throw ValidationException::withMessages(['items'=>__('subcontract_receipt.items_required')]);
            $before=$document->load('items')->toArray();
            foreach($items->groupBy('product_id')->sortKeys() as $productId=>$lines){
                $product=Product::whereKey($productId)->lockForUpdate()->firstOrFail();
                if(!$product->is_active||!$product->track_stock||$product->product_type!=='stock')throw ValidationException::withMessages(['items'=>__('subcontract_receipt.invalid_product')]);
                ProductStock::firstOrCreate(['product_id'=>$productId],['quantity'=>0,'average_unit_cost'=>0]);
                $stock=ProductStock::where('product_id',$productId)->lockForUpdate()->firstOrFail();
                // No invoice costing formula is inferred: retain the stock's existing average unit cost.
                $unitCost=(float)$stock->average_unit_cost;
                foreach($lines as $item){
                    $qty=round((float)$item->quantity,4);
                    if($qty<=0)throw ValidationException::withMessages(['items'=>__('subcontract_receipt.invalid_quantity')]);
                    $balance=round((float)$stock->quantity,4);$after=round($balance+$qty,4);
                    if($after>999999999999.9999)throw ValidationException::withMessages(['stock'=>__('subcontract_receipt.quantity_overflow')]);
                    $stock->update(['quantity'=>$after]);
                    $movement=StockMovement::create(['product_id'=>$productId,'subcontract_material_issue_document_id'=>$document->id,'subcontract_material_issue_item_id'=>$item->id,'created_by'=>$userId,'movement_type'=>'subcontract_receipt','quantity'=>$qty,'quantity_before'=>$balance,'quantity_after'=>$after,'cost_amount'=>round($qty*$unitCost,2),'notes'=>__('subcontract_receipt.stock_reference',['number'=>$document->number])]);
                    $lot=InventoryLot::create(['product_id'=>$productId,'lot_number'=>'SUBREC-'.$document->id.'-'.$item->id,'received_at'=>$movement->created_at,'initial_quantity'=>$qty,'current_quantity'=>$qty,'unit_cost'=>$unitCost,'source_type'=>'subcontract_receipt']);
                    $movement->lotAllocations()->create(['inventory_lot_id'=>$lot->id,'quantity'=>$qty,'unit_cost'=>$unitCost]);
                }
            }
            $document->forceFill(['status'=>'confirmed','confirmed_by'=>$userId,'confirmed_at'=>now()])->save();
            app(AuditLogService::class)->log('CONFIRM','SUBCONTRACT_RECEIPT',"Confirmed subcontract receipt {$document->number}",$document,$before,$document->fresh()->load('items')->toArray(),$userId);
        });
    }
}

<?php
namespace App\Domain\Subcontract\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Models\{Product,ProductStock,StockMovement,SubcontractMaterialIssueDocument};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmSubcontractMaterialIssueAction
{
    public function __invoke(SubcontractMaterialIssueDocument $document,int $companyId,int $userId): void
    {
        DB::transaction(function()use($document,$companyId,$userId){
            $document=SubcontractMaterialIssueDocument::where('company_id',$companyId)->whereIn('issue_kind',['raw','wip'])->lockForUpdate()->findOrFail($document->id);
            $translation=$document->issue_kind==='wip'?'subcontract_wip_issue':'subcontract_material_issue';
            if($document->status!=='draft')throw ValidationException::withMessages(['document'=>__($translation.'.already_confirmed')]);
            $items=$document->items()->orderBy('product_id')->orderBy('id')->lockForUpdate()->get();
            if($items->isEmpty())throw ValidationException::withMessages(['items'=>__($translation.'.items_required')]);
            $before=$document->load('items')->toArray();
            // Consistent lock order; repeated product lines consume the same locked stock sequentially.
            foreach($items->groupBy('product_id')->sortKeys() as $productId=>$lines){
                $product=Product::whereKey($productId)->lockForUpdate()->firstOrFail();
                $productQuery=app($document->issue_kind==='wip'?\App\Domain\Subcontract\Queries\SubcontractWipIssueQuery::class:\App\Domain\Subcontract\Queries\SubcontractMaterialIssueQuery::class);
                if(!$productQuery->products()->whereKey($productId)->exists())throw ValidationException::withMessages(['items'=>__($translation.'.invalid_product')]);
                $stock=ProductStock::where('product_id',$productId)->lockForUpdate()->first();
                $required=round($lines->sum('quantity'),4);
                if(!$stock||(float)$stock->quantity+0.0000001<$required)throw ValidationException::withMessages(['stock'=>__($translation.'.stock_shortage',['item'=>$product->code])]);
                foreach($lines as $item){
                    $qty=round((float)$item->quantity,4);
                    if($qty<=0)throw ValidationException::withMessages(['items'=>__($translation.'.invalid_quantity')]);
                    $balance=round((float)$stock->quantity,4);$after=round($balance-$qty,4);
                    $stock->update(['quantity'=>$after]);
                    // The existing created hook allocates FIFO lots and rejects insufficient lot balances.
                    $movement=StockMovement::create([
                        'product_id'=>$productId,'subcontract_material_issue_document_id'=>$document->id,'subcontract_material_issue_item_id'=>$item->id,
                        'created_by'=>$userId,'movement_type'=>'subcontract_material_issue','quantity'=>-$qty,'quantity_before'=>$balance,'quantity_after'=>$after,
                        'cost_amount'=>0,'notes'=>__($translation.'.stock_reference',['number'=>$document->number]),
                    ]);
                    $cost=$movement->lotAllocations()->get()->sum(fn($a)=>abs((float)$a->quantity)*(float)$a->unit_cost);
                    $movement->update(['cost_amount'=>round($cost,2)]);
                }
            }
            $document->forceFill(['status'=>'confirmed','confirmed_by'=>$userId,'confirmed_at'=>now()])->save();
            app(AuditLogService::class)->log('CONFIRM','SUBCONTRACT_MATERIAL_ISSUE',"Confirmed warehouse issue {$document->number}",$document,$before,$document->fresh()->load('items')->toArray(),$userId);
        });
    }
}

<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\{Product, ProductionReturnDocument};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateProductionReturnAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $audit) {}
    public function __invoke(ProductionReturnDocument $document, array $data, int $userId): ProductionReturnDocument
    {
        return DB::transaction(function()use($document,$data,$userId){
            $locked=ProductionReturnDocument::query()->where('company_id',$this->company->id())->with('items')->lockForUpdate()->findOrFail($document->id);
            if($locked->status!=='pending') throw ValidationException::withMessages(['document'=>__('ui.production_return.only_pending_edit')]);
            $product=Product::query()->where('track_stock',true)->where('is_active',true)->findOrFail($data['product_id']);
            $orderId=$data['raw_material_order_document_id']??null;
            if($orderId){$order=\App\Models\RawMaterialOrderDocument::query()->where('company_id',$this->company->id())->findOrFail($orderId);if(!$order->items()->where('product_id',$product->id)->exists())throw ValidationException::withMessages(['product_id'=>__('ui.production_return.item_not_in_order')]);}
            $old=$locked->load('items')->toArray();$locked->update(['return_date'=>$data['return_date'],'raw_material_order_document_id'=>$orderId,'po_supplier_number'=>$data['po_supplier_number']??null]);
            $item=$locked->items->first();if(!$item)throw ValidationException::withMessages(['document'=>__('ui.production_return.no_items')]);
            $item->update(['product_id'=>$product->id,'quantity'=>$data['quantity'],'description'=>$data['description']??null]);
            $this->audit->log('UPDATE','RETURN_TO_WAREHOUSE',"Memperbarui retur produksi {$locked->number}",$locked,$old,$locked->fresh('items')->toArray(),$userId);
            return $locked->fresh(['items.product.unit']);
        });
    }
}

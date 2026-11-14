<?php

namespace App\Domain\Inventory\Queries;

use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class StockCardQuery
{
    use AppliesDateRange;

    public function products(): Builder
    {
        return Product::query()
            ->where('track_stock', true)
            ->with('unit:id,name,symbol')
            ->select(['id', 'code', 'name', 'unit_id', 'track_stock'])
            ->orderBy('name');
    }

    public function movements(?int $productId, ?string $fromDate, ?string $toDate): Builder
    {
        $query = StockMovement::query()
            ->with([
                'product:id,code,name,unit_id',
                'product.unit:id,name,symbol',
                'goodsReceipt:id,number',
                'purchaseReturn:id,number',
                'stockOpname:id,number',
                'damagedItemDocument:id,number',
                'lostItemDocument:id,number',
                'materialRequestItem.request:id,number',
                'materialIssueDocument:id,number',
                'nonProductionMaterialUsageDocument:id,number',
                'wipIssueDocument:id,number',
                'scrapReceiptDocument:id,number',
                'productionReturnDocument:id,number',
                'productionOrderStageEntry' => fn ($query) => $query->withTrashed()->select('id', 'production_order_id', 'packing_number', 'sewing_number', 'cutting_number'),
                'productionOrderStageEntry.productionOrder:id,number',
                'lotAllocations.inventoryLot:id,lot_number',
            ])
            ->select([
                'id',
                'product_id',
                'goods_receipt_id',
                'purchase_return_id',
                'stock_opname_id',
                'damaged_item_document_id',
                'lost_item_document_id',
                'material_request_item_id',
                'material_issue_document_id',
                'non_production_material_usage_document_id',
                'wip_issue_document_id',
                'scrap_receipt_document_id',
                'production_return_document_id',
                'production_order_stage_entry_id',
                'created_by',
                'movement_type',
                'quantity',
                'quantity_before',
                'quantity_after',
                'notes',
                'created_at',
            ])
            ->when($productId, fn(Builder $builder) => $builder->where('product_id', $productId), fn(Builder $builder) => $builder->whereRaw('1 = 0'))
            ->orderBy('created_at')
            ->orderBy('id');

        return $this->applyDateRange($query, 'created_at', $fromDate, $toDate);
    }

    public function summary(Product $product, ?string $fromDate, ?string $toDate): array
    {
        $movements = StockMovement::query()->where('product_id', $product->id);
        $openingBalance = 0.0;

        if ($fromDate) {
            $from = CarbonImmutable::parse($fromDate)->startOfDay();
            $previousMovement = (clone $movements)
                ->where('created_at', '<', $from)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first(['quantity_after']);
            $openingBalance = (float) ($previousMovement?->quantity_after ?? 0);
        }

        $periodMovements = $this->applyDateRange(clone $movements, 'created_at', $fromDate, $toDate);
        $quantityIn = (clone $periodMovements)->where('quantity', '>', 0)->sum('quantity');
        $quantityOut = abs((float) (clone $periodMovements)->where('quantity', '<', 0)->sum('quantity'));
        $currentBalance = (float) (ProductStock::query()->where('product_id', $product->id)->value('quantity') ?? 0);

        return [
            'opening_balance' => $openingBalance,
            'quantity_in' => (float) $quantityIn,
            'quantity_out' => $quantityOut,
            'current_balance' => $currentBalance,
        ];
    }
}

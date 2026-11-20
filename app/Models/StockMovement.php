<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'subcontract_material_issue_document_id',
        'subcontract_material_issue_item_id',
        'product_id',
        'goods_receipt_id',
        'goods_receipt_item_id',
        'purchase_return_id',
        'purchase_return_item_id',
        'stock_opname_id',
        'stock_opname_item_id',
        'damaged_item_document_id',
        'damaged_item_line_id',
        'lost_item_document_id',
        'lost_item_line_id',
        'material_request_item_id',
        'material_issue_document_id',
        'material_issue_item_id',
        'non_production_material_usage_document_id',
        'non_production_material_usage_item_id',
        'production_return_document_id',
        'production_return_item_id',
        'production_order_stage_entry_id',
        'goods_sent_note_id', 'goods_sent_note_item_id',
        'wip_issue_document_id', 'wip_issue_line_id',
        'scrap_receipt_document_id',
        'scrap_issue_document_id',
        'created_by',
        'movement_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'cost_amount',
        'notes',
    ];

    protected static function booted(): void
    {
        static::created(function (StockMovement $movement) {
            app(\App\Domain\Inventory\Services\StockMovementLotAllocator::class)->allocate($movement);
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'quantity_before' => 'decimal:4',
            'quantity_after' => 'decimal:4',
            'cost_amount' => 'decimal:2',
        ];
    }

    public function subcontractMaterialIssueDocument(): BelongsTo
    {
        return $this->belongsTo(SubcontractMaterialIssueDocument::class,'subcontract_material_issue_document_id')->withTrashed();
    }

    public function scopeWithTransactionDimensions(Builder $query): Builder
    {
        return $query
            ->where(fn ($query) => $query->whereNotNull('goods_receipt_item_id')
                ->orWhereNotNull('purchase_return_item_id')
                ->orWhereNotNull('material_issue_item_id')
                ->orWhereNotNull('material_request_item_id')
                ->orWhereNotNull('stock_opname_item_id')
                ->orWhereNotNull('production_order_stage_entry_id'))
            ->with([
                'goodsReceiptItem.purchaseOrderItem.purchaseRequestItem.materialRequestItem',
                'purchaseReturnItem.goodsReceiptItem.purchaseOrderItem.purchaseRequestItem.materialRequestItem',
                'materialIssueItem.rawMaterialOrderItem.sourceMaterial',
                'materialIssueItem.requestItem',
                'materialRequestItem',
                'stockOpnameItem:id,product_id',
                'stockOpnameItem.product:id,color,size',
                'productionOrderStageEntry' => fn ($entry) => $entry->withTrashed()->select('id', 'production_order_id'),
                'productionOrderStageEntry.productionOrder:id,order_color,order_size',
            ]);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }

    public function purchaseReturnItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturnItem::class);
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function stockOpnameItem(): BelongsTo
    {
        return $this->belongsTo(StockOpnameItem::class);
    }

    public function damagedItemDocument(): BelongsTo
    {
        return $this->belongsTo(DamagedItemDocument::class);
    }

    public function lostItemDocument(): BelongsTo
    {
        return $this->belongsTo(LostItemDocument::class);
    }


    public function materialRequestItem(): BelongsTo
    {
        return $this->belongsTo(MaterialRequestItem::class);
    }

    public function materialIssueDocument(): BelongsTo
    {
        return $this->belongsTo(MaterialIssueDocument::class);
    }

    public function materialIssueItem(): BelongsTo
    {
        return $this->belongsTo(MaterialIssueItem::class);
    }

    public function nonProductionMaterialUsageDocument(): BelongsTo
    {
        return $this->belongsTo(NonProductionMaterialUsageDocument::class, 'non_production_material_usage_document_id');
    }

    public function nonProductionMaterialUsageItem(): BelongsTo
    {
        return $this->belongsTo(NonProductionMaterialUsageItem::class, 'non_production_material_usage_item_id');
    }

    public function productionReturnDocument(): BelongsTo
    {
        return $this->belongsTo(ProductionReturnDocument::class);
    }

    public function productionReturnItem(): BelongsTo
    {
        return $this->belongsTo(ProductionReturnItem::class);
    }

    public function productionOrderStageEntry(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderStageEntry::class);
    }

    public function goodsSentNote(): BelongsTo
    {
        return $this->belongsTo(GoodsSentNote::class);
    }

    public function wipIssueDocument(): BelongsTo
    {
        return $this->belongsTo(WipIssueDocument::class, 'wip_issue_document_id');
    }

    public function scrapReceiptDocument(): BelongsTo
    {
        return $this->belongsTo(ScrapReceiptDocument::class, 'scrap_receipt_document_id');
    }

    public function scrapIssueDocument(): BelongsTo
    {
        return $this->belongsTo(ScrapIssueDocument::class, 'scrap_issue_document_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lotAllocations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockMovementLotAllocation::class);
    }
}

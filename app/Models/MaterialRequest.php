<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialRequest extends Model
{
    protected $fillable = [
        'production_order_header_id', 'source_details', 'number', 'requested_by', 'production_order_id', 'bill_of_material_id', 'output_product_id', 'bom_version', 'production_quantity', 'department', 'needed_at', 'reason', 'status',
        'purchase_status', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected function casts(): array
    {
        return ['source_details' => 'array', 'needed_at' => 'date', 'reviewed_at' => 'datetime', 'production_quantity' => 'decimal:4'];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaterialRequestItem::class);
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function materialIssues(): HasMany
    {
        return $this->hasMany(MaterialIssueDocument::class, 'material_request_id');
    }

    public function scopeWithDeletionBlockers(Builder $query): Builder
    {
        return $query->withExists([
            'purchaseRequests',
            'materialIssues',
            'items as has_used_items' => fn (Builder $items) => $items->where(fn (Builder $linked) => $linked
                ->where('issued_quantity', '>', 0)
                ->orWhereHas('purchaseRequestItems')
                ->orWhereHas('materialIssueItems')
                ->orWhereHas('stockMovements')),
        ]);
    }

    public function hasDownstreamTransactions(): bool
    {
        return $this->purchase_requests_exists || $this->material_issues_exists || $this->has_used_items;
    }

    public function outputProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'output_product_id');
    }

    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class)->withTrashed();
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function productionOrderHeader(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderHeader::class, 'production_order_header_id');
    }
}

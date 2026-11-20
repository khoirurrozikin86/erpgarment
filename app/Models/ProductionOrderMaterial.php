<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderMaterial extends Model
{
    protected $fillable = [
        'production_order_id', 'bill_of_material_id', 'product_id', 'color', 'size', 'operation', 'quantity_per_output',
        'wastage_percent', 'required_quantity', 'issued_quantity', 'consumed_quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity_per_output' => 'decimal:4', 'wastage_percent' => 'decimal:3',
            'required_quantity' => 'decimal:4', 'issued_quantity' => 'decimal:4',
            'consumed_quantity' => 'decimal:4',
        ];
    }

    public function scopeFromActiveBom(Builder $query, int $companyId, string $category = 'all'): Builder
    {
        $eligibleBom = fn (Builder $bom) => $bom->where('company_id', $companyId)->where('status', 'active')
            ->whereNull('bill_of_materials.deleted_at')
            ->when($category !== 'all', fn (Builder $source) => $source->where('bom_type', $category));

        return $query->where(fn (Builder $materials) => $materials->whereHas('billOfMaterial', $eligibleBom)
            ->orWhere(fn (Builder $legacy) => $legacy->whereNull('bill_of_material_id')
                ->whereHas('productionOrder.billOfMaterial', $eligibleBom)));
    }

    public function scopeForSewing(Builder $query): Builder
    {
        return $query
            ->whereDoesntHave('billOfMaterial', fn (Builder $bom) => $bom->where('bom_type', 'fabric'))
            ->whereHas('product', fn (Builder $product) => $product->where(function (Builder $product) {
                $product->whereNull('garment_item_type')->orWhere('garment_item_type', '<>', 'fabric');
            }));
    }

    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
    public function billOfMaterial(): BelongsTo { return $this->belongsTo(BillOfMaterial::class)->withTrashed(); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category_id',
        'unit_id',
        'product_type',
        'garment_item_type',
        'style_code',
        'color',
        'size',
        'purchase_price',
        'min_stock',
        'max_stock',
        'reorder_point',
        'track_stock',
        'is_active',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',

            'min_stock' => 'decimal:4',
            'max_stock' => 'decimal:4',
            'reorder_point' => 'decimal:4',


            'track_stock' => 'boolean',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function billsOfMaterial(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class);
    }

    public function billOfMaterialComponents(): HasMany
    {
        return $this->hasMany(BillOfMaterialItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)
            ->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)
            ->where('is_primary', true);
    }

    public function stock(): HasOne
    {
        return $this->hasOne(ProductStock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockOpnameItems(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }
}

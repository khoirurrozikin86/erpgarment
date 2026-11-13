<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialOrderItem extends Model
{
    protected $fillable = ['company_id', 'number', 'issued_quantity', 'raw_material_order_document_id', 'production_order_material_id', 'product_id', 'style_code', 'quantity'];
    protected function casts(): array { return ['quantity' => 'decimal:4', 'issued_quantity' => 'decimal:4']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function document(): BelongsTo { return $this->belongsTo(RawMaterialOrderDocument::class, 'raw_material_order_document_id'); }
    public function sourceMaterial(): BelongsTo { return $this->belongsTo(ProductionOrderMaterial::class, 'production_order_material_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function materialIssueItems(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(MaterialIssueItem::class); }
    public function latestMaterialIssueItem(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(MaterialIssueItem::class)->latestOfMany(); }
}

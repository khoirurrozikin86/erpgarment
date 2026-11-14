<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionReturnDocument extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'number', 'return_date', 'raw_material_order_document_id', 'po_supplier_number', 'status', 'created_by', 'approved_by', 'approved_at', 'cancelled_by', 'cancelled_at'];
    protected function casts(): array { return ['return_date' => 'date', 'approved_at' => 'datetime', 'cancelled_at' => 'datetime']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function rawMaterialOrder(): BelongsTo { return $this->belongsTo(RawMaterialOrderDocument::class, 'raw_material_order_document_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(ProductionReturnItem::class); }
}

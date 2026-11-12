<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillOfMaterial extends Model
{
    protected $fillable = [
        'company_id', 'product_id', 'version', 'name', 'status', 'output_quantity',
        'effective_from', 'effective_until', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['output_quantity' => 'decimal:4', 'effective_from' => 'date', 'effective_until' => 'date'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(BillOfMaterialItem::class)->orderBy('id'); }
}

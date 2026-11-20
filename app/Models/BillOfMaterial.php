<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillOfMaterial extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'product_id', 'style_code', 'bom_type', 'number', 'version', 'name', 'status', 'output_quantity',
        'effective_from', 'effective_until', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['output_quantity' => 'decimal:4', 'effective_from' => 'date', 'effective_until' => 'date'];
    }

    protected static function booted(): void
    {
        static::created(function (self $bom) {
            if (!$bom->number) {
                $bom->forceFill(['number' => 'BOM-'.str_pad((string) $bom->id, 8, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(BillOfMaterialItem::class)->orderBy('id'); }
}

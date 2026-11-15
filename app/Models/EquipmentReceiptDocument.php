<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentReceiptDocument extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'number', 'receipt_date', 'ownership_type', 'invoice_number', 'invoice_date', 'supplier_id', 'currency_code', 'description', 'created_by'];

    protected function casts(): array
    {
        return ['receipt_date' => 'date', 'invoice_date' => 'date'];
    }

    public function items(): HasMany { return $this->hasMany(EquipmentReceiptItem::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}

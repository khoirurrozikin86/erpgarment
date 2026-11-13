<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterialOrderDocument extends Model
{
    protected $fillable = ['company_id', 'number', 'order_date', 'production_order_number', 'created_by'];
    protected function casts(): array { return ['order_date' => 'date']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(RawMaterialOrderItem::class); }
}

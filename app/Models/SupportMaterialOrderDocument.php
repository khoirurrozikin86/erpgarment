<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportMaterialOrderDocument extends Model
{
    use SoftDeletes;
    protected $fillable = ['company_id', 'number', 'order_date', 'created_by'];
    protected function casts(): array { return ['order_date' => 'date']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(SupportMaterialOrderItem::class, 'document_id'); }
}

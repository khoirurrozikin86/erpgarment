<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubcontractSemiFinishedDocument extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'number', 'production_date', 'production_order_id', 'buyer_po_number', 'description', 'created_by'];
    protected function casts(): array { return ['production_date' => 'date']; }
    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(SubcontractSemiFinishedItem::class, 'document_id'); }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsTo};

class ProductionOrderHeader extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['po_date' => 'date', 'due_date' => 'date']; }
    public function details(): HasMany { return $this->hasMany(ProductionOrder::class, 'production_order_header_id'); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function buyer(): BelongsTo { return $this->belongsTo(Customer::class, 'customer_id'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderStageEntry extends Model
{
    protected $fillable = [
        'production_order_id', 'stage', 'input_quantity', 'good_quantity',
        'rejected_quantity', 'waste_quantity', 'notes', 'processed_by', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'input_quantity' => 'decimal:4', 'good_quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4', 'waste_quantity' => 'decimal:4',
            'processed_at' => 'datetime',
        ];
    }

    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
    public function processor(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
}

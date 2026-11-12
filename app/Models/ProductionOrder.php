<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    protected $fillable = [
        'company_id', 'number', 'bill_of_material_id', 'output_product_id', 'bom_version',
        'planned_quantity', 'planned_start_date', 'planned_end_date', 'status', 'notes',
        'created_by', 'released_by', 'released_at',
    ];

    protected function casts(): array
    {
        return [
            'planned_quantity' => 'decimal:4',
            'planned_start_date' => 'date',
            'planned_end_date' => 'date',
            'released_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function billOfMaterial(): BelongsTo { return $this->belongsTo(BillOfMaterial::class); }
    public function outputProduct(): BelongsTo { return $this->belongsTo(Product::class, 'output_product_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function releaser(): BelongsTo { return $this->belongsTo(User::class, 'released_by'); }
    public function materials(): HasMany { return $this->hasMany(ProductionOrderMaterial::class); }
    public function materialRequests(): HasMany { return $this->hasMany(MaterialRequest::class); }
    public function stageEntries(): HasMany { return $this->hasMany(ProductionOrderStageEntry::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    protected $fillable = [
        'image_path', 'production_order_header_id', 'style_code', 'order_color', 'order_size',
        'company_id', 'number', 'buyer_po_number', 'no_cbd', 'currency', 'destination', 'po_date', 'due_date', 'customer_id', 'bill_of_material_id', 'output_product_id', 'bom_version',
        'planned_quantity', 'price_per_unit', 'total_price', 'description', 'planned_start_date', 'planned_end_date', 'status', 'notes',
        'created_by', 'released_by', 'released_at',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? '/storage/' . ltrim($this->image_path, '/') : $this->outputProduct?->mo_image_url;
    }

    public function stageProductCode(string $stage): ?string
    {
        $code = $this->outputProduct?->parentProduct?->code ?? $this->outputProduct?->code;
        $suffix = match ($stage) {
            'cutting' => '/CUT',
            'sewing' => '/SEW',
            'packing' => '/PKG',
            default => '',
        };

        return filled($code) ? $code.$suffix : null;
    }

    public function getModelStyleAttribute(): ?string
    {
        return $this->style_code ?? $this->outputProduct?->style_code;
    }

    public function getLineColorAttribute(): ?string
    {
        return $this->order_color ?? $this->outputProduct?->color;
    }

    public function getLineSizeAttribute(): ?string
    {
        return $this->order_size ?? $this->outputProduct?->size;
    }

    protected function casts(): array
    {
        return [
            'planned_quantity' => 'decimal:4',
            'price_per_unit' => 'decimal:4',
            'total_price' => 'decimal:4',
            'po_date' => 'date',
            'due_date' => 'date',
            'planned_start_date' => 'date',
            'planned_end_date' => 'date',
            'released_at' => 'datetime',
        ];
    }

    public function hasOrderMaterialRequests(): bool
    {
        return $this->production_order_header_id && MaterialRequest::where('production_order_header_id', $this->production_order_header_id)->exists();
    }

    public function header(): BelongsTo { return $this->belongsTo(ProductionOrderHeader::class, 'production_order_header_id'); }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function billOfMaterial(): BelongsTo { return $this->belongsTo(BillOfMaterial::class)->withTrashed(); }
    public function outputProduct(): BelongsTo { return $this->belongsTo(Product::class, 'output_product_id'); }
    public function buyer(): BelongsTo { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function releaser(): BelongsTo { return $this->belongsTo(User::class, 'released_by'); }
    public function materials(): HasMany { return $this->hasMany(ProductionOrderMaterial::class); }
    public function materialRequests(): HasMany { return $this->hasMany(MaterialRequest::class); }
    public function stageEntries(): HasMany { return $this->hasMany(ProductionOrderStageEntry::class); }
}

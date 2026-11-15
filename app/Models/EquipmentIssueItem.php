<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentIssueItem extends Model
{
    protected $fillable = ['equipment_issue_document_id', 'product_id', 'item_code', 'item_name', 'unit', 'quantity', 'description'];
    protected $casts = ['quantity' => 'decimal:4'];

    public function document(): BelongsTo { return $this->belongsTo(EquipmentIssueDocument::class, 'equipment_issue_document_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}

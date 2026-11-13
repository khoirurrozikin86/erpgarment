<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialIssueItem extends Model
{
    protected $fillable = ['material_issue_document_id', 'material_request_item_id', 'raw_material_order_item_id', 'product_id', 'quantity'];
    protected function casts(): array { return ['quantity' => 'decimal:4']; }
    public function document(): BelongsTo { return $this->belongsTo(MaterialIssueDocument::class, 'material_issue_document_id'); }
    public function requestItem(): BelongsTo { return $this->belongsTo(MaterialRequestItem::class, 'material_request_item_id'); }
    public function rawMaterialOrderItem(): BelongsTo { return $this->belongsTo(RawMaterialOrderItem::class, 'raw_material_order_item_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}

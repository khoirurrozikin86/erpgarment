<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentReceiptItem extends Model
{
    protected $fillable = ['equipment_receipt_document_id', 'product_id', 'item_code', 'item_name', 'unit', 'quantity', 'unit_price', 'total_price', 'supplier_po_number', 'description'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4', 'unit_price' => 'decimal:4', 'total_price' => 'decimal:4'];
    }

    public function document(): BelongsTo { return $this->belongsTo(EquipmentReceiptDocument::class, 'equipment_receipt_document_id'); }
}

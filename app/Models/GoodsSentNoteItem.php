<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsSentNoteItem extends Model
{
    use SoftDeletes;

    protected $fillable = ['goods_sent_note_id', 'production_order_id', 'product_id', 'customer_id', 'quantity', 'cartons'];
    protected function casts(): array { return ['quantity' => 'decimal:4', 'cartons' => 'decimal:4']; }
    public function note(): BelongsTo { return $this->belongsTo(GoodsSentNote::class, 'goods_sent_note_id'); }
    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
}

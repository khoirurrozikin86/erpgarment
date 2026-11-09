<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesQuotationEmailLog extends Model
{
    protected $fillable = ['sales_quotation_id', 'sent_by', 'recipient', 'subject', 'sent_at'];

    protected function casts(): array { return ['sent_at' => 'datetime']; }

    public function quotation(): BelongsTo { return $this->belongsTo(SalesQuotation::class, 'sales_quotation_id'); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sent_by'); }
}

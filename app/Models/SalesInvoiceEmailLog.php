<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceEmailLog extends Model
{
    protected $fillable = ['sales_invoice_id', 'sent_by', 'recipient', 'subject', 'sent_at'];
    protected function casts(): array { return ['sent_at' => 'datetime']; }
    public function invoice(): BelongsTo { return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id'); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sent_by'); }
}

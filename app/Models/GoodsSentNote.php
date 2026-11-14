<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsSentNote extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'number', 'sent_date', 'contract_number', 'contract_date', 'delivery_route', 'warehouse', 'vehicle_number', 'driver_phone', 'driver_name', 'seal_number', 'receiver_type', 'container_number', 'created_by', 'approved_by', 'approved_at'];
    protected function casts(): array { return ['sent_date' => 'date', 'contract_date' => 'date', 'approved_at' => 'datetime']; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(GoodsSentNoteItem::class); }
}

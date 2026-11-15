<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasOne};

class EquipmentIssueDocument extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'number', 'issue_date', 'created_by'];
    protected $casts = ['issue_date' => 'date'];

    public function item(): HasOne { return $this->hasOne(EquipmentIssueItem::class, 'equipment_issue_document_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}

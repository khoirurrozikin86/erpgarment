<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalEntry extends Model
{
    protected $fillable = ['company_id', 'number', 'journal_date', 'reference_number', 'description', 'source_type', 'source_id', 'source_action', 'total_debit', 'total_credit', 'status', 'created_by', 'posted_by', 'posted_at'];

    protected function casts(): array
    {
        return ['journal_date' => 'date', 'total_debit' => 'decimal:2', 'total_credit' => 'decimal:2', 'posted_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function poster(): BelongsTo { return $this->belongsTo(User::class, 'posted_by'); }
    public function lines(): HasMany { return $this->hasMany(JournalEntryLine::class)->orderBy('line_number'); }
    public function source(): MorphTo { return $this->morphTo(); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WipIssueDocument extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'number', 'transaction_date', 'created_by'];
    protected function casts(): array { return ['transaction_date' => 'date']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(WipIssueLine::class, 'document_id'); }
}

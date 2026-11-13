<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DamagedItemDocument extends Model
{
    protected $fillable = ['number', 'document_date', 'created_by'];

    protected function casts(): array { return ['document_date' => 'date']; }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(DamagedItemLine::class); }
}

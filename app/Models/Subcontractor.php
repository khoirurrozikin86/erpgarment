<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;

class Subcontractor extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'name', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function contracts(): HasMany { return $this->hasMany(SubcontractContract::class); }
}

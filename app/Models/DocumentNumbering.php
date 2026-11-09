<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentNumbering extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'document_type',
        'prefix',
        'format',
        'next_number',
        'number_length',
        'reset_period',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
            'number_length' => 'integer',
            'last_reset_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

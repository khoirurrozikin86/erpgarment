<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GeneralSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'decimal_places',
        'negative_stock',
        'tax_included',
    ];

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'negative_stock' => 'boolean',
            'tax_included' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

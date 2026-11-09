<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'supplier_type',
        'contact_person',
        'phone',
        'email',
        'website',
        'tax_number',
        'address',
        'city',
        'province',
        'postal_code',
        'payment_term',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}

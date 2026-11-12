<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exporter extends Model
{
    protected $fillable = ['tax_number', 'name', 'address', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}

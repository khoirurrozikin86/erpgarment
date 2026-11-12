<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Importer extends Model
{
    protected $fillable = ['tax_number', 'name', 'address', 'apip', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}

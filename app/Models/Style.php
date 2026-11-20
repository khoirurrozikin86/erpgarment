<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Style extends Model
{
    protected $fillable = ['code', 'name'];

    public static function register(?string $code): ?string
    {
        $code = trim($code ?? '');
        if ($code === '') {
            return null;
        }

        return static::firstOrCreate(['code' => $code], ['name' => $code])->code;
    }
}

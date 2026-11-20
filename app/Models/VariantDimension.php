<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class VariantDimension extends Model
{
    protected $fillable = ['kind', 'code', 'name', 'sort_order', 'canonical_id'];

    public function aliases(): HasMany
    {
        return $this->hasMany(self::class, 'canonical_id');
    }

    public function canonical(): BelongsTo
    {
        return $this->belongsTo(self::class, 'canonical_id');
    }

    public function scopeOptions(Builder $query, string $kind): Builder
    {
        return $query->where('kind', $kind)->whereNull('canonical_id')->orderBy('sort_order')->orderBy('code');
    }

    public static function normalize(string $value): string
    {
        return mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));
    }

    public static function resolve(string $kind, string $value, string $field, int $row): string
    {
        $entry = static::where('kind', $kind)->where('code', static::normalize($value))->with('canonical')->first();
        if (!$entry) {
            throw ValidationException::withMessages([$field => __('variants.unknown_dimension', [
                'row' => $row, 'field' => __('ui.workflow.'.$kind), 'code' => $value,
            ])]);
        }

        return $entry->canonical?->code ?? $entry->code;
    }
}

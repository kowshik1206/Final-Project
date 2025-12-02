<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'effective_from',
        'effective_to',
        'active',
    ];

    protected $casts = [
        'value' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'active' => 'boolean',
    ];

    public static function getActive(string $key)
    {
        return static::where('key', $key)
            ->where('active', true)
            ->latest()
            ->first();
    }

    public static function getActiveValue(string $key, $default = null)
    {
        $config = static::getActive($key);
        return $config?->value ?? $default;
    }
}

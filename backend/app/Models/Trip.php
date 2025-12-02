<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Trip Model
 * 
 * Represents user trips with route data, cost, and preferences.
 */
class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'origin',
        'destination',
        'waypoints',
        'polyline',
        'distance_meters',
        'duration_seconds',
        'mode',
        'cost',
        'passengers',
        'saved_preferences',
    ];

    protected $casts = [
        'origin' => 'array',
        'destination' => 'array',
        'waypoints' => 'array',
        'cost' => 'array',
        'saved_preferences' => 'array',
        'passengers' => 'integer',
        'distance_meters' => 'float',
        'duration_seconds' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDistanceKmAttribute()
    {
        return $this->distance_meters / 1000;
    }

    public function getDurationMinutesAttribute()
    {
        return ceil($this->duration_seconds / 60);
    }
}

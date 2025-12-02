<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * POI Model
 * 
 * Represents Points of Interest (restaurants, gas stations, etc.)
 */
class Poi extends Model
{
    use HasFactory;

    protected $table = 'pois';

    protected $fillable = [
        'name',
        'type',
        'latitude',
        'longitude',
        'address',
        'tags',
        'attributes',
        'created_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'attributes' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCoordinatesAttribute()
    {
        return [
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }

    /**
     * Calculate distance from coordinates (Haversine formula)
     */
    public function distanceFrom(float $latitude, float $longitude): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($this->latitude - $latitude);
        $dLon = deg2rad($this->longitude - $longitude);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($latitude)) * cos(deg2rad($this->latitude)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Query POIs near a route (within radius in meters)
     * 
     * @param Builder $query
     * @param float $lat Route latitude
     * @param float $lng Route longitude
     * @param float $radiusMeters Search radius in meters (default 5000m)
     * @return Builder
     */
    public function scopeNearRoute(Builder $query, float $lat, float $lng, float $radiusMeters = 5000): Builder
    {
        $radiusKm = $radiusMeters / 1000;

        // Using Haversine formula for distance calculation
        // d = 2 * R * arcsin(sqrt(sin²(Δlat/2) + cos(lat1) * cos(lat2) * sin²(Δlng/2)))
        return $query->selectRaw(
            "*, 
            (6371 * 2 * ASIN(SQRT(
                POWER(SIN(RADIANS((latitude - ?) / 2)), 2) +
                COS(RADIANS(?)) * COS(RADIANS(latitude)) *
                POWER(SIN(RADIANS((longitude - ?) / 2)), 2)
            ))) as distance_km",
            [$lat, $lat, $lng]
        )
            ->whereRaw(
                "(6371 * 2 * ASIN(SQRT(
                    POWER(SIN(RADIANS((latitude - ?) / 2)), 2) +
                    COS(RADIANS(?)) * COS(RADIANS(latitude)) *
                    POWER(SIN(RADIANS((longitude - ?) / 2)), 2)
                ))) <= ?",
                [$lat, $lat, $lng, $radiusKm]
            )
            ->orderBy('distance_km', 'asc');
    }
}

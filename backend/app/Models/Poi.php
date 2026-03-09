<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poi extends Model
{
    use HasFactory;

    protected $table = 'pois';

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'category',
        'description',
        'rating',
        'reviews_count',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'rating' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship with Trip
     */
    public function trips()
    {
        return $this->belongsToMany(Trip::class);
    }

    /**
     * Calculate distance from coordinates
     */
    public function distanceFrom($latitude, $longitude)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($this->latitude - $latitude);
        $dLon = deg2rad($this->longitude - $longitude);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($latitude)) * cos(deg2rad($this->latitude)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }
}

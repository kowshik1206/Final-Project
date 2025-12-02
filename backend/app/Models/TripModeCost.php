<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripModeCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'mode',
        'base_cost',
        'distance_cost',
        'time_cost',
        'total_cost',
    ];

    protected $casts = [
        'base_cost' => 'float',
        'distance_cost' => 'float',
        'time_cost' => 'float',
        'total_cost' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship with Trip
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}

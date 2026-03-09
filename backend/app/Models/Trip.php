<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'start_location',
        'end_location',
        'mode',
        'distance',
        'duration',
        'cost',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with TripModeCost
     */
    public function modeCosts()
    {
        return $this->hasMany(TripModeCost::class);
    }

    /**
     * Relationship with Poi
     */
    public function pois()
    {
        return $this->belongsToMany(Poi::class);
    }
}

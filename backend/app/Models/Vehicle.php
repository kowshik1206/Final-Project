<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'fuel_efficiency_km_per_l',
        'ev_kwh_per_km',
        'fuel_price_per_l',
        'ev_price_per_kwh',
        'capacity_passengers',
    ];

    protected $casts = [
        'fuel_efficiency_km_per_l' => 'decimal:2',
        'ev_kwh_per_km' => 'decimal:3',
        'fuel_price_per_l' => 'decimal:2',
        'ev_price_per_kwh' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

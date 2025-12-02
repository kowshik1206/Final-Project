<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CostConfigSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now()->toDateTimeString();

        $defaults = [
            // driver and fuel assumptions
            'driver_rate_per_km' => 10.0,
            'default_fuel_efficiency_km_per_l' => 15.0,
            'default_price_per_l' => 95.0,
            // ev
            'ev_kwh_per_km' => 0.15,
            'price_per_kwh' => 9.0,
            // toll as percentage of fuel cost by default
            'toll_rate_percent' => 5.0,
            // train fares
            'train_base_fare' => 50.0,
            'train_fare_per_km' => 1.0,
            // flight
            'flight_airport_fee' => 500.0,
            'flight_cost_per_km' => 1.5,
            'flight_fuel_surcharge' => 0.05 // 5% surcharge
        ];

        foreach ($defaults as $k => $v) {
            DB::table('cost_configs')->updateOrInsert(
                ['key' => $k],
                ['value' => json_encode($v), 'active' => true, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }
}

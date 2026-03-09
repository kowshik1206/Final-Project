<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('vehicle_profiles')->insert([
            // Petrol vehicles
            [
                'name' => 'Maruti Swift',
                'fuel' => 'petrol',
                'efficiency_km_per_unit' => 18.5,
                'unit' => 'L',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hyundai i20',
                'fuel' => 'petrol',
                'efficiency_km_per_unit' => 17.2,
                'unit' => 'L',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tata Nexon',
                'fuel' => 'petrol',
                'efficiency_km_per_unit' => 16.8,
                'unit' => 'L',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Diesel vehicles
            [
                'name' => 'Toyota Innova',
                'fuel' => 'diesel',
                'efficiency_km_per_unit' => 12.5,
                'unit' => 'L',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mahindra XUV500',
                'fuel' => 'diesel',
                'efficiency_km_per_unit' => 14.2,
                'unit' => 'L',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ford Endeavour',
                'fuel' => 'diesel',
                'efficiency_km_per_unit' => 11.8,
                'unit' => 'L',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // CNG vehicles
            [
                'name' => 'Maruti Alto CNG',
                'fuel' => 'cng',
                'efficiency_km_per_unit' => 22.5,
                'unit' => 'kg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tata Tiago CNG',
                'fuel' => 'cng',
                'efficiency_km_per_unit' => 21.0,
                'unit' => 'kg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hyundai Santro CNG',
                'fuel' => 'cng',
                'efficiency_km_per_unit' => 20.5,
                'unit' => 'kg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Electric vehicles
            [
                'name' => 'Tesla Model 3',
                'fuel' => 'electric',
                'efficiency_km_per_unit' => 6.0,
                'unit' => 'kWh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tata Nexon EV',
                'fuel' => 'electric',
                'efficiency_km_per_unit' => 5.2,
                'unit' => 'kWh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'MG ZS EV',
                'fuel' => 'electric',
                'efficiency_km_per_unit' => 5.5,
                'unit' => 'kWh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

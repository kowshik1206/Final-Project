<?php

namespace Database\Seeders;

use App\Models\Poi;
use App\Models\User;
use Illuminate\Database\Seeder;

class PoiSeeder extends Seeder
{
    /**
     * Seed POIs across major Indian cities and highway routes.
     */
    public function run(): void
    {
        // Get or create admin user for POI creation
        $admin = User::firstOrCreate(
            ['email' => 'admin@routeiq.com'],
            [
                'name' => 'RouteIQ Admin',
                'password' => bcrypt('admin123'),
                'phone' => '9876543210',
            ]
        );

        // Fuel stations along major routes
        $fuelStations = [
            ['name' => 'HP Petrol Pump - Delhi', 'lat' => 28.6139, 'lng' => 77.2090, 'tags' => ['diesel', 'petrol', 'fast-track']],
            ['name' => 'Indian Oil - Noida', 'lat' => 28.5921, 'lng' => 77.0970, 'tags' => ['diesel', 'petrol', 'cng']],
            ['name' => 'Shell Station - Gurgaon', 'lat' => 28.4595, 'lng' => 77.0266, 'tags' => ['diesel', 'petrol', 'premium']],
            ['name' => 'BPCL Pump - Agra', 'lat' => 27.1767, 'lng' => 78.0081, 'tags' => ['diesel', 'petrol']],
            ['name' => 'Essar Petrol - Jaipur', 'lat' => 26.8124, 'lng' => 75.8158, 'tags' => ['diesel', 'petrol', 'atm']],
            ['name' => 'Reliance Jio Station - Mumbai', 'lat' => 19.0760, 'lng' => 72.8777, 'tags' => ['diesel', 'petrol', 'ev-ready']],
            ['name' => 'HP Pump - Pune', 'lat' => 18.5204, 'lng' => 73.8567, 'tags' => ['diesel', 'petrol']],
            ['name' => 'Indian Oil - Bangalore', 'lat' => 12.9716, 'lng' => 77.5946, 'tags' => ['diesel', 'petrol', 'atm']],
            ['name' => 'Shell - Hyderabad', 'lat' => 17.3850, 'lng' => 78.4867, 'tags' => ['diesel', 'petrol']],
            ['name' => 'BPCL - Chennai', 'lat' => 13.0827, 'lng' => 80.2707, 'tags' => ['diesel', 'petrol']],
        ];

        foreach ($fuelStations as $station) {
            Poi::create([
                'name' => $station['name'],
                'type' => 'fuel',
                'latitude' => $station['lat'],
                'longitude' => $station['lng'],
                'address' => $station['name'],
                'tags' => $station['tags'],
                'attributes' => json_encode(['operating_hours' => '24/7', 'accepts_cards' => true]),
                'created_by' => $admin->id,
            ]);
        }

        // EV Charging stations
        $chargers = [
            ['name' => 'Tata Power Charging - Delhi', 'lat' => 28.6139, 'lng' => 77.2090, 'tags' => ['fast-charging', 'tesla', '350kw']],
            ['name' => 'MG Charging Hub - Gurgaon', 'lat' => 28.4595, 'lng' => 77.0266, 'tags' => ['fast-charging', 'ac-charging']],
            ['name' => 'Fortum - Mumbai', 'lat' => 19.0760, 'lng' => 72.8777, 'tags' => ['fast-charging', '150kw']],
            ['name' => 'Charge Zone - Bangalore', 'lat' => 12.9716, 'lng' => 77.5946, 'tags' => ['fast-charging', 'dc-charging']],
            ['name' => 'ABB Charging - Pune', 'lat' => 18.5204, 'lng' => 73.8567, 'tags' => ['fast-charging']],
            ['name' => 'Shell Recharge - Hyderabad', 'lat' => 17.3850, 'lng' => 78.4867, 'tags' => ['fast-charging']],
            ['name' => 'Okaya EV Charger - Jaipur', 'lat' => 26.8124, 'lng' => 75.8158, 'tags' => ['ac-charging']],
            ['name' => 'ChargeX - Chennai', 'lat' => 13.0827, 'lng' => 80.2707, 'tags' => ['fast-charging', 'reserved']],
        ];

        foreach ($chargers as $charger) {
            Poi::create([
                'name' => $charger['name'],
                'type' => 'charger',
                'latitude' => $charger['lat'],
                'longitude' => $charger['lng'],
                'address' => $charger['name'],
                'tags' => $charger['tags'],
                'attributes' => json_encode(['power_output' => '350kW', 'availability' => 'available']),
                'created_by' => $admin->id,
            ]);
        }

        // Toll plazas
        $tolls = [
            ['name' => 'Ghazipur Toll Plaza - Delhi', 'lat' => 28.6600, 'lng' => 77.3000],
            ['name' => 'Manesar Toll - Haryana', 'lat' => 28.3600, 'lng' => 77.1500],
            ['name' => 'Noida Toll Bridge', 'lat' => 28.5800, 'lng' => 77.3800],
            ['name' => 'Mumbai-Pune Toll - Talegaon', 'lat' => 18.9800, 'lng' => 73.5600],
            ['name' => 'Chennai Bypass Toll', 'lat' => 13.1500, 'lng' => 80.2800],
        ];

        foreach ($tolls as $toll) {
            Poi::create([
                'name' => $toll['name'],
                'type' => 'toll',
                'latitude' => $toll['lat'],
                'longitude' => $toll['lng'],
                'address' => $toll['name'],
                'tags' => ['fastag-enabled', '24-7'],
                'attributes' => json_encode(['payment_methods' => ['card', 'cash', 'fastag']]),
                'created_by' => $admin->id,
            ]);
        }

        // Restaurants
        $restaurants = [
            ['name' => 'Highway Dhaba - Delhi', 'lat' => 28.5500, 'lng' => 77.2500, 'tags' => ['vegetarian', 'non-veg', 'parking']],
            ['name' => 'Bikaneri Bhujia Restaurant', 'lat' => 28.4000, 'lng' => 77.1000, 'tags' => ['vegetarian', 'fast-food']],
            ['name' => 'Mumbai Roadside Café', 'lat' => 19.0800, 'lng' => 72.8800, 'tags' => ['veg-vegan', 'wifi']],
            ['name' => 'Bangalore Highway Stoppage', 'lat' => 12.9500, 'lng' => 77.5500, 'tags' => ['multi-cuisine', 'parking']],
        ];

        foreach ($restaurants as $restaurant) {
            Poi::create([
                'name' => $restaurant['name'],
                'type' => 'restaurant',
                'latitude' => $restaurant['lat'],
                'longitude' => $restaurant['lng'],
                'address' => $restaurant['name'],
                'tags' => $restaurant['tags'],
                'attributes' => json_encode(['avg_rating' => rand(3, 5), 'seating_capacity' => rand(20, 50)]),
                'created_by' => $admin->id,
            ]);
        }

        // Hospitals
        $hospitals = [
            ['name' => 'Apollo Hospital - Delhi', 'lat' => 28.5600, 'lng' => 77.2300, 'tags' => ['emergency', '24-7', 'icu']],
            ['name' => 'Max Hospital - Gurgaon', 'lat' => 28.4000, 'lng' => 77.0500, 'tags' => ['emergency', 'trauma-center']],
            ['name' => 'Lilavati Hospital - Mumbai', 'lat' => 19.0300, 'lng' => 72.8200, 'tags' => ['emergency', '24-7']],
            ['name' => 'Manipal Hospital - Bangalore', 'lat' => 12.9300, 'lng' => 77.6100, 'tags' => ['emergency', 'icu']],
        ];

        foreach ($hospitals as $hospital) {
            Poi::create([
                'name' => $hospital['name'],
                'type' => 'hospital',
                'latitude' => $hospital['lat'],
                'longitude' => $hospital['lng'],
                'address' => $hospital['name'],
                'tags' => $hospital['tags'],
                'attributes' => json_encode(['beds' => rand(100, 500), 'specialties' => ['emergency', 'cardiology', 'trauma']]),
                'created_by' => $admin->id,
            ]);
        }

        // Temples
        $temples = [
            ['name' => 'Krishna Temple - Mathura', 'lat' => 27.4924, 'lng' => 77.6737, 'tags' => ['pilgrimage', 'accommodation', 'parking']],
            ['name' => 'Vaishno Devi Temple - Katra', 'lat' => 32.7325, 'lng' => 75.3331, 'tags' => ['pilgrimage', 'trek']],
            ['name' => 'Tirupati Temple - Andhra Pradesh', 'lat' => 13.1827, 'lng' => 79.8243, 'tags' => ['pilgrimage', 'accommodation']],
            ['name' => 'Golden Temple - Amritsar', 'lat' => 31.6200, 'lng' => 74.8765, 'tags' => ['pilgrimage', 'free-meal', 'accommodation']],
        ];

        foreach ($temples as $temple) {
            Poi::create([
                'name' => $temple['name'],
                'type' => 'temple',
                'latitude' => $temple['lat'],
                'longitude' => $temple['lng'],
                'address' => $temple['name'],
                'tags' => $temple['tags'],
                'attributes' => json_encode(['opening_hours' => '5:00 AM - 11:00 PM', 'entry_fee' => 'Free']),
                'created_by' => $admin->id,
            ]);
        }

        $this->command->info('✓ 50+ POIs seeded successfully across major Indian routes');
    }
}

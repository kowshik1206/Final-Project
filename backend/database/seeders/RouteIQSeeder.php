<?php

namespace Database\Seeders;

use App\Models\CostConfig;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class RouteIQSeeder extends Seeder
{
    /**
     * Seed the application's database with sample RouteIQ data.
     */
    public function run(): void
    {
        // Seed cost configurations
        $this->seedCostConfigs();

        // Seed sample users with vehicles and trips
        $this->seedUsersWithVehiclesAndTrips();
    }

    private function seedCostConfigs(): void
    {
        $configs = [
            // Car configurations
            [
                'key' => 'car_fuel_efficiency_km_per_l',
                'value' => json_encode(['value' => 15, 'unit' => 'km/l', 'description' => 'Average fuel efficiency for petrol cars']),
                'active' => true,
            ],
            [
                'key' => 'car_fuel_price_per_l',
                'value' => json_encode(['value' => 95, 'currency' => 'INR', 'description' => 'Current fuel price']),
                'active' => true,
            ],
            [
                'key' => 'car_driver_cost_per_km',
                'value' => json_encode(['value' => 10, 'currency' => 'INR', 'description' => 'Driver cost per kilometer']),
                'active' => true,
            ],
            [
                'key' => 'car_toll_multiplier',
                'value' => json_encode(['value' => 0.05, 'description' => 'Toll cost as percentage of distance']),
                'active' => true,
            ],

            // EV configurations
            [
                'key' => 'ev_kwh_per_km',
                'value' => json_encode(['value' => 0.2, 'unit' => 'kWh/km']),
                'active' => true,
            ],
            [
                'key' => 'ev_price_per_kwh',
                'value' => json_encode(['value' => 15, 'currency' => 'INR']),
                'active' => true,
            ],
            [
                'key' => 'ev_charging_loss_factor',
                'value' => json_encode(['value' => 1.1, 'description' => 'Loss factor during charging']),
                'active' => true,
            ],
            [
                'key' => 'ev_battery_degradation_per_km',
                'value' => json_encode(['value' => 0.5, 'currency' => 'INR']),
                'active' => true,
            ],

            // Train configurations
            [
                'key' => 'train_base_fare',
                'value' => json_encode(['value' => 100, 'currency' => 'INR']),
                'active' => true,
            ],
            [
                'key' => 'train_fare_per_km',
                'value' => json_encode(['value' => 2, 'currency' => 'INR']),
                'active' => true,
            ],
            [
                'key' => 'train_discount_200km',
                'value' => json_encode(['value' => 1.5, 'currency' => 'INR', 'threshold_km' => 200]),
                'active' => true,
            ],
            [
                'key' => 'train_discount_500km',
                'value' => json_encode(['value' => 1.2, 'currency' => 'INR', 'threshold_km' => 500]),
                'active' => true,
            ],

            // Flight configurations
            [
                'key' => 'flight_airport_fee',
                'value' => json_encode(['value' => 200, 'currency' => 'INR']),
                'active' => true,
            ],
            [
                'key' => 'flight_cost_per_km',
                'value' => json_encode(['value' => 2.5, 'currency' => 'INR']),
                'active' => true,
            ],
            [
                'key' => 'flight_tax',
                'value' => json_encode(['value' => 500, 'currency' => 'INR']),
                'active' => true,
            ],
            [
                'key' => 'flight_fuel_surcharge_percentage',
                'value' => json_encode(['value' => 10, 'unit' => 'percentage']),
                'active' => true,
            ],
        ];

        foreach ($configs as $config) {
            CostConfig::updateOrCreate(
                ['key' => $config['key']],
                $config
            );
        }

        $this->command->info('✓ Cost configurations seeded');
    }

    private function seedUsersWithVehiclesAndTrips(): void
    {
        $users = [
            [
                'name' => 'Rajesh Kumar',
                'email' => 'rajesh@example.com',
                'phone' => '9876543210',
                'vehicles' => [
                    ['type' => 'petrol', 'fuel_efficiency_km_per_l' => 15, 'fuel_price_per_l' => 95, 'capacity_passengers' => 5],
                    ['type' => 'ev', 'ev_kwh_per_km' => 0.2, 'ev_price_per_kwh' => 15, 'capacity_passengers' => 5],
                ],
            ],
            [
                'name' => 'Priya Singh',
                'email' => 'priya@example.com',
                'phone' => '9876543211',
                'vehicles' => [
                    ['type' => 'petrol', 'fuel_efficiency_km_per_l' => 12, 'fuel_price_per_l' => 95, 'capacity_passengers' => 4],
                ],
            ],
            [
                'name' => 'Amit Patel',
                'email' => 'amit@example.com',
                'phone' => '9876543212',
                'vehicles' => [
                    ['type' => 'ev', 'ev_kwh_per_km' => 0.22, 'ev_price_per_kwh' => 15, 'capacity_passengers' => 5],
                ],
            ],
            [
                'name' => 'Neha Verma',
                'email' => 'neha@example.com',
                'phone' => '9876543213',
                'vehicles' => [
                    ['type' => 'petrol', 'fuel_efficiency_km_per_l' => 18, 'fuel_price_per_l' => 95, 'capacity_passengers' => 5],
                ],
            ],
            [
                'name' => 'Vikram Sharma',
                'email' => 'vikram@example.com',
                'phone' => '9876543214',
                'vehicles' => [
                    ['type' => 'diesel', 'fuel_efficiency_km_per_l' => 22, 'fuel_price_per_l' => 92, 'capacity_passengers' => 7],
                ],
            ],
        ];

        $sampleTrips = [
            [
                'name' => 'Delhi to Agra',
                'origin' => ['lat' => 28.6139, 'lng' => 77.2090, 'name' => 'Delhi'],
                'destination' => ['lat' => 27.1767, 'lng' => 78.0081, 'name' => 'Agra'],
                'distance_meters' => 206000,
                'mode' => 'car',
                'passengers' => 2,
            ],
            [
                'name' => 'Delhi to Jaipur',
                'origin' => ['lat' => 28.6139, 'lng' => 77.2090, 'name' => 'Delhi'],
                'destination' => ['lat' => 26.8124, 'lng' => 75.8158, 'name' => 'Jaipur'],
                'distance_meters' => 268000,
                'mode' => 'train',
                'passengers' => 1,
            ],
            [
                'name' => 'Mumbai to Pune',
                'origin' => ['lat' => 19.0760, 'lng' => 72.8777, 'name' => 'Mumbai'],
                'destination' => ['lat' => 18.5204, 'lng' => 73.8567, 'name' => 'Pune'],
                'distance_meters' => 149000,
                'mode' => 'car',
                'passengers' => 3,
            ],
            [
                'name' => 'Bangalore to Hyderabad',
                'origin' => ['lat' => 12.9716, 'lng' => 77.5946, 'name' => 'Bangalore'],
                'destination' => ['lat' => 17.3850, 'lng' => 78.4867, 'name' => 'Hyderabad'],
                'distance_meters' => 580000,
                'mode' => 'flight',
                'passengers' => 2,
            ],
            [
                'name' => 'Chennai to Coimbatore',
                'origin' => ['lat' => 13.0827, 'lng' => 80.2707, 'name' => 'Chennai'],
                'destination' => ['lat' => 11.0026, 'lng' => 76.7055, 'name' => 'Coimbatore'],
                'distance_meters' => 276000,
                'mode' => 'train',
                'passengers' => 4,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => bcrypt('password123'),
                'phone' => $userData['phone'],
                'settings' => json_encode([
                    'preferred_mode' => 'car',
                    'notifications_enabled' => true,
                ]),
            ]);

            // Create vehicles
            foreach ($userData['vehicles'] as $vehicleData) {
                Vehicle::create([
                    'user_id' => $user->id,
                    ...$vehicleData,
                ]);
            }

            // Create some sample trips
            $tripsToCreate = array_slice($sampleTrips, 0, rand(2, 4));
            foreach ($tripsToCreate as $tripData) {
                Trip::create([
                    'user_id' => $user->id,
                    'name' => $tripData['name'],
                    'origin' => $tripData['origin'],
                    'destination' => $tripData['destination'],
                    'waypoints' => [],
                    'polyline' => 'sample_polyline',
                    'distance_meters' => $tripData['distance_meters'],
                    'duration_seconds' => intval($tripData['distance_meters'] / 80 * 3.6), // ~80 km/h average
                    'mode' => $tripData['mode'],
                    'cost' => [
                        [
                            'mode' => $tripData['mode'],
                            'total_cost' => rand(500, 5000),
                            'breakdown' => [],
                        ],
                    ],
                    'passengers' => $tripData['passengers'],
                    'saved_preferences' => [],
                ]);
            }
        }

        $this->command->info('✓ Users, vehicles, and trips seeded');
    }
}

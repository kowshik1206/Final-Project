<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Poi;

class PoiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pois = [
            [
                'name' => 'Eiffel Tower',
                'latitude' => 48.8584,
                'longitude' => 2.2945,
                'category' => 'landmark',
                'description' => 'Iconic iron tower in Paris',
                'rating' => 4.7,
                'reviews_count' => 5000,
            ],
            [
                'name' => 'Statue of Liberty',
                'latitude' => 40.6892,
                'longitude' => -74.0445,
                'category' => 'landmark',
                'description' => 'Colossal neoclassical sculpture',
                'rating' => 4.6,
                'reviews_count' => 4500,
            ],
            [
                'name' => 'Big Ben',
                'latitude' => 51.4975,
                'longitude' => -0.1250,
                'category' => 'landmark',
                'description' => 'Great Bell of the clock at the Palace of Westminster',
                'rating' => 4.5,
                'reviews_count' => 4000,
            ],
            [
                'name' => 'Colosseum',
                'latitude' => 41.8902,
                'longitude' => 12.4923,
                'category' => 'landmark',
                'description' => 'Ancient Roman amphitheatre',
                'rating' => 4.8,
                'reviews_count' => 6000,
            ],
            [
                'name' => 'Central Park',
                'latitude' => 40.7829,
                'longitude' => -73.9654,
                'category' => 'park',
                'description' => 'Urban park in Manhattan',
                'rating' => 4.7,
                'reviews_count' => 5500,
            ],
        ];

        foreach ($pois as $poi) {
            Poi::create($poi);
        }
    }
}

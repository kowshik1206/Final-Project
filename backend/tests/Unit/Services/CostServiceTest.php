<?php

namespace Tests\Unit\Services;

use App\Services\CostService;
use App\Services\DTOs\RouteData;
use App\Services\DTOs\VehicleData;
use Tests\TestCase;

class CostServiceTest extends TestCase
{
    protected CostService $costService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->costService = new CostService();
    }

    public function test_car_cost_calculation()
    {
        $route = new RouteData(
            distanceMeters: 100000, // 100 km
            durationSeconds: 7200,
            polyline: 'test',
            points: [],
            preference: 'driving'
        );

        $vehicle = new VehicleData(
            type: 'petrol',
            fuelEfficiencyKmPerL: 15,
            evKwhPerKm: 0,
            fuelPricePerL: 95,
            evPricePerKwh: 0
        );

        $breakdowns = $this->costService->calculateCosts($route, $vehicle, ['car'], 2);

        $this->assertCount(1, $breakdowns);
        $this->assertEquals('car', $breakdowns[0]->mode);

        // Expected: fuel = (100/15)*95 = 633.33, driver = 100*10 = 1000, tolls = 5, taxes = 5%
        // Total ≈ 1720
        $this->assertGreaterThan(1600, $breakdowns[0]->totalCost);
        $this->assertLessThan(1800, $breakdowns[0]->totalCost);
    }

    public function test_ev_cost_calculation()
    {
        $route = new RouteData(
            distanceMeters: 100000, // 100 km
            durationSeconds: 7200,
            polyline: 'test',
            points: [],
            preference: 'driving'
        );

        $vehicle = new VehicleData(
            type: 'ev',
            fuelEfficiencyKmPerL: 0,
            evKwhPerKm: 0.2,
            fuelPricePerL: 0,
            evPricePerKwh: 15
        );

        $breakdowns = $this->costService->calculateCosts($route, $vehicle, ['ev'], 2);

        $this->assertCount(1, $breakdowns);
        $this->assertEquals('ev', $breakdowns[0]->mode);

        // Expected: energy = 100 * 0.2 * 1.1 * 15 = 330, degradation = 100 * 0.5 = 50, maintenance = 100 * 0.2 = 20
        // Total ≈ 400
        $this->assertGreaterThan(300, $breakdowns[0]->totalCost);
        $this->assertLessThan(500, $breakdowns[0]->totalCost);
    }

    public function test_train_cost_calculation()
    {
        $route = new RouteData(
            distanceMeters: 100000, // 100 km
            durationSeconds: 7200,
            polyline: 'test',
            points: [],
            preference: 'transit'
        );

        $vehicle = new VehicleData(
            type: 'train',
            fuelEfficiencyKmPerL: 0,
            evKwhPerKm: 0,
            fuelPricePerL: 0,
            evPricePerKwh: 0
        );

        $breakdowns = $this->costService->calculateCosts($route, $vehicle, ['train'], 2);

        $this->assertCount(1, $breakdowns);
        $this->assertEquals('train', $breakdowns[0]->mode);

        // Expected: base = 100 + 100 * 2 = 300
        $this->assertGreaterThan(200, $breakdowns[0]->totalCost);
    }

    public function test_train_cost_with_slab_discount()
    {
        $route = new RouteData(
            distanceMeters: 300000, // 300 km (triggers >200km discount)
            durationSeconds: 14400,
            polyline: 'test',
            points: [],
            preference: 'transit'
        );

        $vehicle = new VehicleData(
            type: 'train',
            fuelEfficiencyKmPerL: 0,
            evKwhPerKm: 0,
            fuelPricePerL: 0,
            evPricePerKwh: 0
        );

        $breakdowns = $this->costService->calculateCosts($route, $vehicle, ['train'], 2);

        // Expected: base = 100 + 300 * 1.5 = 550 (discounted rate)
        $this->assertGreaterThan(400, $breakdowns[0]->totalCost);
    }

    public function test_flight_cost_calculation()
    {
        $route = new RouteData(
            distanceMeters: 1000000, // 1000 km
            durationSeconds: 7200,
            polyline: 'test',
            points: [],
            preference: 'flying'
        );

        $vehicle = new VehicleData(
            type: 'flight',
            fuelEfficiencyKmPerL: 0,
            evKwhPerKm: 0,
            fuelPricePerL: 0,
            evPricePerKwh: 0
        );

        $breakdowns = $this->costService->calculateCosts($route, $vehicle, ['flight'], 2);

        $this->assertCount(1, $breakdowns);
        $this->assertEquals('flight', $breakdowns[0]->mode);

        // Expected: airport = 200 + distance_cost = 1000*2.5 = 2500 + tax = 500 + fuel_surcharge = 10%
        // Total ≈ 3355
        $this->assertGreaterThan(3000, $breakdowns[0]->totalCost);
    }

    public function test_multiple_modes_calculation()
    {
        $route = new RouteData(
            distanceMeters: 500000, // 500 km
            durationSeconds: 36000,
            polyline: 'test',
            points: [],
            preference: 'driving'
        );

        $vehicle = new VehicleData(
            type: 'petrol',
            fuelEfficiencyKmPerL: 15,
            evKwhPerKm: 0.2,
            fuelPricePerL: 95,
            evPricePerKwh: 15
        );

        $breakdowns = $this->costService->calculateCosts(
            $route,
            $vehicle,
            ['car', 'train', 'flight'],
            4
        );

        $this->assertCount(3, $breakdowns);
        $this->assertEquals('car', $breakdowns[0]->mode);
        $this->assertEquals('train', $breakdowns[1]->mode);
        $this->assertEquals('flight', $breakdowns[2]->mode);

        // Train should be cheapest for 500km
        $costs = collect($breakdowns)->pluck('totalCost')->toArray();
        $this->assertEquals(min($costs), $breakdowns[1]->totalCost);
    }

    public function test_cost_breakdown_contains_all_fields()
    {
        $route = new RouteData(
            distanceMeters: 100000,
            durationSeconds: 7200,
            polyline: 'test',
            points: [],
            preference: 'driving'
        );

        $vehicle = new VehicleData(
            type: 'petrol',
            fuelEfficiencyKmPerL: 15,
            evKwhPerKm: 0,
            fuelPricePerL: 95,
            evPricePerKwh: 0
        );

        $breakdowns = $this->costService->calculateCosts($route, $vehicle, ['car'], 2);
        $breakdown = $breakdowns[0];

        $this->assertNotNull($breakdown->mode);
        $this->assertGreaterThan(0, $breakdown->totalCost);
        $this->assertNotEmpty($breakdown->breakdown);
        $this->assertNotEmpty($breakdown->assumptions);
        $this->assertNotEmpty($breakdown->explanation);
    }
}

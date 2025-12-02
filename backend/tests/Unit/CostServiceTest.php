<?php

namespace Tests\Unit;

use App\Services\CostService;
use PHPUnit\Framework\TestCase;

/**
 * CostServiceTest
 *
 * Tests deterministic cost calculations for all modes.
 * Each test verifies exact output matching specification.
 */
class CostServiceTest extends TestCase
{
    protected CostService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new CostService();
    }

    /**
     * Test CAR calculation: 100km with default params
     * fuel_eff=15, fuel_price=95, driver_rate=10, toll=5% of fuel_cost
     */
    public function testCarCost100km(): void
    {
        $input = [
            'distance_meters' => 100000,
            'mode_preferences' => ['car'],
        ];

        $result = $this->svc->calculate($input);
        $car = $result['car'];

        // fuel_needed = 100 / 15 = 6.667
        // fuel_cost = 6.667 * 95 = 633.33
        // driver_cost = 100 * 10 = 1000
        // toll_cost = 633.33 * 0.05 = 31.67
        // total = 633.33 + 1000 + 31.67 = 1665.00

        $this->assertSame('car', $car['mode']);
        $this->assertSame(1665.00, $car['total_cost']);
        $this->assertSame(633.33, $car['breakdown']['fuel_cost']);
        $this->assertSame(1000.00, $car['breakdown']['driver_cost']);
        $this->assertSame(31.67, $car['breakdown']['toll_cost']);
        $this->assertNotEmpty($car['explanation']);
    }

    /**
     * Test EV calculation: 100km with default params
     * ev_kwh_per_km=0.2, price_per_kwh=15, loss_factor=1.1
     */
    public function testEvCost100km(): void
    {
        $input = [
            'distance_meters' => 100000,
            'mode_preferences' => ['ev'],
        ];

        $result = $this->svc->calculate($input);
        $ev = $result['ev'];

        // energy_kwh = 100 * 0.2 = 20
        // energy_cost = 20 * 15 = 300
        // total = 300 * 1.1 = 330.00

        $this->assertSame('ev', $ev['mode']);
        $this->assertSame(330.00, $ev['total_cost']);
        $this->assertSame(300.00, $ev['breakdown']['energy_cost']);
        $this->assertNotEmpty($ev['explanation']);
    }

    /**
     * Test TRAIN calculation: 100km (< 200km threshold, no discount)
     * base_fare=100, fare_per_km=2
     */
    public function testTrainCost100km(): void
    {
        $input = [
            'distance_meters' => 100000,
            'mode_preferences' => ['train'],
        ];

        $result = $this->svc->calculate($input);
        $train = $result['train'];

        // per_km_fare = 100 * 2 = 200 (no discount)
        // total = 100 + 200 = 300.00

        $this->assertSame('train', $train['mode']);
        $this->assertSame(300.00, $train['total_cost']);
        $this->assertSame(100.00, $train['breakdown']['base_fare']);
        $this->assertStringContainsString('₹300', $train['explanation']);
    }

    /**
     * Test TRAIN calculation: 300km (> 200km threshold, 10% discount applies)
     * base_fare=100, fare_per_km=2, discount 10% on per-km portion
     */
    public function testTrainCost300kmWithDiscount(): void
    {
        $input = [
            'distance_meters' => 300000,
            'mode_preferences' => ['train'],
        ];

        $result = $this->svc->calculate($input);
        $train = $result['train'];

        // per_km_fare = 300 * 2 = 600
        // per_km_fare after discount = 600 * 0.9 = 540
        // total = 100 + 540 = 640.00

        $this->assertSame('train', $train['mode']);
        $this->assertSame(640.00, $train['total_cost']);
        $this->assertSame(100.00, $train['breakdown']['base_fare']);
        $this->assertStringContainsString('discount', $train['explanation']);
    }

    /**
     * Test FLIGHT calculation: 1000km
     * airport_fee=200, cost_per_km=2.5, fuel_surcharge=10%
     */
    public function testFlightCost1000km(): void
    {
        $input = [
            'distance_meters' => 1000000,
            'mode_preferences' => ['flight'],
        ];

        $result = $this->svc->calculate($input);
        $flight = $result['flight'];

        // distance_cost = 1000 * 2.5 = 2500
        // subtotal = 200 + 2500 = 2700
        // fuel_surcharge = 2700 * 0.1 = 270
        // total = 2700 + 270 = 2970.00

        $this->assertSame('flight', $flight['mode']);
        $this->assertSame(2970.00, $flight['total_cost']);
        $this->assertSame(200.00, $flight['breakdown']['base_fare']);
        $this->assertSame(270.00, $flight['breakdown']['tax']);
        $this->assertNotEmpty($flight['explanation']);
    }

    /**
     * Test multi-mode calculation (determinism: same input = same output)
     */
    public function testMultiModeDeterminism(): void
    {
        $input = [
            'distance_meters' => 250000,
            'mode_preferences' => ['car', 'ev', 'train', 'flight'],
        ];

        $result1 = $this->svc->calculate($input);
        $result2 = $this->svc->calculate($input);

        // Identical calls must produce identical results
        $this->assertSame($result1, $result2, 'Multi-mode calculation must be deterministic');
    }
}

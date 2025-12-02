<?php

namespace Tests\Unit;

use App\Services\MapServiceFake;
use PHPUnit\Framework\TestCase;

/**
 * MapServiceFakeTest
 *
 * Validates deterministic behavior of MapServiceFake.
 * Key assertion: haversineMeters must return identical distance for identical input across runs.
 */
class MapServiceFakeTest extends TestCase
{
    protected MapServiceFake $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new MapServiceFake();
    }

    /**
     * Test haversineMeters determinism
     *
     * Verify that the Haversine distance calculation is deterministic:
     * same input (lat/lng pairs) must always produce identical output.
     */
    public function testHaversineMetersDeterminism(): void
    {
        // San Francisco to New York (approximately 4135 km)
        $lat1 = 37.7749;
        $lng1 = -122.4194;
        $lat2 = 40.7128;
        $lng2 = -74.0060;

        $distance1 = $this->svc->haversineMeters($lat1, $lng1, $lat2, $lng2);
        $distance2 = $this->svc->haversineMeters($lat1, $lng1, $lat2, $lng2);

        // Identical calls must produce identical results
        $this->assertSame($distance1, $distance2, 'Haversine calculation must be deterministic');

        // Verify distance is in reasonable range (~4135 km = 4135000 meters)
        $this->assertGreaterThan(4000000, $distance1, 'SF to NYC distance too small');
        $this->assertLessThan(4500000, $distance1, 'SF to NYC distance too large');
    }

    /**
     * Test calculateRoute returns expected structure
     *
     * Verify that calculateRoute returns proper array with required keys:
     * polyline, distance_meters, duration_seconds, steps, calculated_via
     */
    public function testCalculateRouteStructure(): void
    {
        $points = [
            ['lat' => 37.7749, 'lng' => -122.4194],
            ['lat' => 40.7128, 'lng' => -74.0060]
        ];

        $result = $this->svc->calculateRoute($points, null, 'fastest', 'car');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('polyline', $result);
        $this->assertArrayHasKey('distance_meters', $result);
        $this->assertArrayHasKey('duration_seconds', $result);
        $this->assertArrayHasKey('steps', $result);
        $this->assertArrayHasKey('calculated_via', $result);

        $this->assertIsString($result['polyline']);
        $this->assertIsInt($result['distance_meters']);
        $this->assertIsInt($result['duration_seconds']);
        $this->assertIsArray($result['steps']);
        $this->assertSame('fake', $result['calculated_via']);
    }
}

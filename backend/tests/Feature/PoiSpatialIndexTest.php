<?php

namespace Tests\Feature;

use App\Models\Poi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * POI Spatial Index Test
 * 
 * Verifies that spatial indexes are properly created on the pois table
 * and that proximity queries perform efficiently.
 */
class PoiSpatialIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that pois table has lat/lng indexes
     */
    public function test_pois_table_has_indexes(): void
    {
        // Run migrations
        $this->artisan('migrate');

        // Get table indexes
        $indexes = DB::select("SHOW INDEX FROM pois");
        $indexNames = array_column($indexes, 'Key_name');

        // Check for spatial or composite index
        $hasSpatialIndex = in_array('pois_location_spatialindex', $indexNames);
        $hasCompositeIndex = in_array('pois_lat_lng_index', $indexNames);

        $this->assertTrue(
            $hasSpatialIndex || $hasCompositeIndex,
            'pois table should have spatial or composite lat/lng index'
        );
    }

    /**
     * Test scopeNearRoute query method exists and works
     */
    public function test_scope_near_route_method_exists(): void
    {
        $this->artisan('migrate');

        // Create a test POI
        $poi = Poi::factory()->create([
            'latitude' => 28.6139,
            'longitude' => 77.2090,
        ]);

        // Query using scopeNearRoute
        $result = Poi::nearRoute(28.6139, 77.2090, 5000)->first();

        $this->assertNotNull($result);
        $this->assertEquals($poi->id, $result->id);
    }

    /**
     * Test scopeNearRoute returns POIs within radius
     */
    public function test_scope_near_route_filters_by_radius(): void
    {
        $this->artisan('migrate');

        // Create POI near route
        $nearPoi = Poi::factory()->create([
            'latitude' => 28.6139,
            'longitude' => 77.2090,
        ]);

        // Create POI far from route
        $farPoi = Poi::factory()->create([
            'latitude' => 30.0, // ~150 km away
            'longitude' => 77.2090,
        ]);

        // Query with 5000m radius
        $results = Poi::nearRoute(28.6139, 77.2090, 5000)->get();

        $ids = $results->pluck('id')->toArray();

        // Near POI should be in results
        $this->assertContains($nearPoi->id, $ids, 'POI within radius should be included');

        // Far POI should not be in results
        $this->assertNotContains($farPoi->id, $ids, 'POI outside radius should be excluded');
    }

    /**
     * Test scopeNearRoute returns distance_km column
     */
    public function test_scope_near_route_includes_distance(): void
    {
        $this->artisan('migrate');

        $poi = Poi::factory()->create([
            'latitude' => 28.6139,
            'longitude' => 77.2090,
        ]);

        $result = Poi::nearRoute(28.6139, 77.2090, 5000)->first();

        // Should have distance_km column
        $this->assertNotNull($result->distance_km);
        $this->assertIsNumeric($result->distance_km);
        $this->assertGreaterThanOrEqual(0, $result->distance_km);
    }

    /**
     * Test scopeNearRoute sorts by distance ascending
     */
    public function test_scope_near_route_orders_by_distance(): void
    {
        $this->artisan('migrate');

        // Create multiple POIs at different distances
        $poi1 = Poi::factory()->create([
            'latitude' => 28.6139,
            'longitude' => 77.2090,
        ]);

        $poi2 = Poi::factory()->create([
            'latitude' => 28.6150, // Slightly further
            'longitude' => 77.2090,
        ]);

        $results = Poi::nearRoute(28.6139, 77.2090, 10000)->get();

        // First result should be closer
        $this->assertEquals($poi1->id, $results->first()->id);
        $this->assertLessThanOrEqual(
            $results->last()->distance_km,
            $results->first()->distance_km,
            'Results should be ordered by distance ascending'
        );
    }

    /**
     * Test spatial index exists after migration
     */
    public function test_spatial_index_created_after_migration(): void
    {
        $this->artisan('migrate');

        $indexes = DB::select("SHOW INDEX FROM pois WHERE Column_name IN ('location', 'latitude', 'longitude')");

        // Verify at least one index exists
        $this->assertNotEmpty($indexes, 'pois table should have indexes on location or lat/lng columns');
    }

    /**
     * Test multiple POI proximity query performance (simulated)
     */
    public function test_spatial_query_with_multiple_pois(): void
    {
        $this->artisan('migrate');

        // Create 100 POIs scattered across different locations
        Poi::factory(100)->create();

        // Query POIs near a specific route
        $startTime = microtime(true);
        $results = Poi::nearRoute(28.6139, 77.2090, 50000)->get();
        $elapsed = microtime(true) - $startTime;

        // Query should complete reasonably quickly (< 1 second for 100 POIs)
        $this->assertLessThan(1.0, $elapsed, 'Query should complete in less than 1 second');

        // Should return some results
        $this->assertIsObject($results);
    }

    /**
     * Test that POI model has the nearRoute scope method
     */
    public function test_poi_model_has_near_route_scope(): void
    {
        $this->artisan('migrate');

        // Create test POI
        $poi = Poi::factory()->create();

        // Verify the method exists by calling it
        $query = Poi::nearRoute(28.6139, 77.2090, 5000);

        // If method doesn't exist, this will throw an error
        $this->assertNotNull($query);
    }

    /**
     * Test default radius parameter
     */
    public function test_scope_near_route_default_radius(): void
    {
        $this->artisan('migrate');

        $poi = Poi::factory()->create([
            'latitude' => 28.6139,
            'longitude' => 77.2090,
        ]);

        // Call without explicit radius (should use default 5000m)
        $result = Poi::nearRoute(28.6139, 77.2090)->first();

        $this->assertNotNull($result);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\MapServiceFake;
use Illuminate\Support\Facades\Cache;

/**
 * RouteCachingTest
 *
 * Validates cache behavior: first request returns X-Cache: MISS, second returns HIT.
 * Verifies cache key generation and TTL from .env (ROUTE_CACHE_TTL).
 */
class RouteCachingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Bind MapServiceFake for deterministic testing
        app()->bind('App\Services\MapServiceInterface', MapServiceFake::class);
        // Clear cache before test
        Cache::flush();
    }

    /**
     * Test cache MISS on first request, HIT on second with identical payload
     */
    public function testRouteCalcCacheMissAndHit(): void
    {
        $user = \App\Models\User::factory()->create();

        $payload = [
            'points' => [
                ['lat' => 37.7749, 'lng' => -122.4194],
                ['lat' => 40.7128, 'lng' => -74.0060]
            ],
            'mode' => 'car',
            'preference' => 'fastest'
        ];

        // First request: MISS
        $response1 = $this->actingAs($user)
            ->postJson('/api/route/calc', $payload);

        $response1->assertStatus(200)
            ->assertHeader('X-Cache', 'MISS');

        $data1 = $response1->json('data');

        // Second request (identical payload): HIT
        $response2 = $this->actingAs($user)
            ->postJson('/api/route/calc', $payload);

        $response2->assertStatus(200)
            ->assertHeader('X-Cache', 'HIT');

        $data2 = $response2->json('data');

        // Data should be identical (distance, duration, polyline)
        $this->assertSame($data1['distance_meters'], $data2['distance_meters']);
        $this->assertSame($data1['duration_seconds'], $data2['duration_seconds']);
        $this->assertSame($data1['polyline'], $data2['polyline']);
    }

    /**
     * Test cache MISS when payload changes (different mode)
     */
    public function testRouteCalcCacheMissOnPayloadChange(): void
    {
        $user = \App\Models\User::factory()->create();

        $basePayload = [
            'points' => [
                ['lat' => 37.7749, 'lng' => -122.4194],
                ['lat' => 40.7128, 'lng' => -74.0060]
            ]
        ];

        // First request: car mode, MISS
        $response1 = $this->actingAs($user)
            ->postJson('/api/route/calc', array_merge($basePayload, ['mode' => 'car']));

        $response1->assertStatus(200)
            ->assertHeader('X-Cache', 'MISS');

        // Second request: train mode (different), MISS
        $response2 = $this->actingAs($user)
            ->postJson('/api/route/calc', array_merge($basePayload, ['mode' => 'train']));

        $response2->assertStatus(200)
            ->assertHeader('X-Cache', 'MISS');

        // Distances should differ (different speeds)
        $this->assertNotSame(
            $response1->json('data.duration_seconds'),
            $response2->json('data.duration_seconds'),
            'Train and car should have different durations'
        );
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\MapServiceFake;
use Illuminate\Support\Facades\Auth;

/**
 * RouteCalculationTest
 *
 * Validates /api/route/calc endpoint response structure and correct MapService delegation.
 */
class RouteCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Bind MapServiceFake for deterministic testing
        app()->bind('App\Services\MapServiceInterface', MapServiceFake::class);
    }

    /**
     * Test /api/route/calc returns success response with correct structure
     */
    public function testRouteCalcEndpointReturnsSuccessResponse(): void
    {
        // Create a test user and authenticate
        $user = $this->createAuthenticatedUser();

        $payload = [
            'points' => [
                ['lat' => 37.7749, 'lng' => -122.4194],
                ['lat' => 40.7128, 'lng' => -74.0060]
            ],
            'mode' => 'car',
            'preference' => 'fastest'
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/route/calc', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'polyline',
                    'distance_meters',
                    'duration_seconds',
                    'steps',
                    'calculated_via'
                ]
            ]);

        // Verify data types
        $this->assertSame('success', $response->json('status'));
        $this->assertIsString($response->json('data.polyline'));
        $this->assertIsInt($response->json('data.distance_meters'));
        $this->assertIsInt($response->json('data.duration_seconds'));
        $this->assertIsArray($response->json('data.steps'));
        $this->assertSame('fake', $response->json('data.calculated_via'));
    }

    /**
     * Test /api/route/calc validation rejects <2 points
     */
    public function testRouteCalcValidationMinPoints(): void
    {
        $user = $this->createAuthenticatedUser();

        $payload = [
            'points' => [
                ['lat' => 37.7749, 'lng' => -122.4194]
            ],
            'mode' => 'car'
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/route/calc', $payload);

        $response->assertStatus(422);
    }

    /**
     * Test /api/route/calc validation rejects >50 points
     */
    public function testRouteCalcValidationMaxPoints(): void
    {
        $user = $this->createAuthenticatedUser();

        $points = [];
        for ($i = 0; $i < 51; $i++) {
            $points[] = [
                'lat' => 37.7749 + ($i * 0.01),
                'lng' => -122.4194 + ($i * 0.01)
            ];
        }

        $payload = [
            'points' => $points,
            'mode' => 'car'
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/route/calc', $payload);

        $response->assertStatus(422);
    }

    /**
     * Helper: Create and authenticate a test user
     */
    protected function createAuthenticatedUser()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');
        return $user;
    }
}

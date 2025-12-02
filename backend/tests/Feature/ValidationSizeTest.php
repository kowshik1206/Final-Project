<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Validation Size Test
 * 
 * Verifies that multi-stop optimization enforces strict waypoint limits.
 * Maximum 7 waypoints enforced due to TSP computational complexity O(7!).
 */
class ValidationSizeTest extends TestCase
{
    /**
     * Test that max 7 waypoints is enforced
     */
    public function test_max_seven_waypoints_enforced(): void
    {
        $payload = [
            'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
            'destination' => ['lat' => 28.5355, 'lng' => 77.3910],
            'waypoints' => [
                ['lat' => 28.5400, 'lng' => 77.3000],
                ['lat' => 28.5500, 'lng' => 77.3100],
                ['lat' => 28.5600, 'lng' => 77.3200],
                ['lat' => 28.5700, 'lng' => 77.3300],
                ['lat' => 28.5800, 'lng' => 77.3400],
                ['lat' => 28.5900, 'lng' => 77.3500],
                ['lat' => 28.6000, 'lng' => 77.3600],
            ],
            'optimize_for' => 'distance',
        ];

        // 7 waypoints should be accepted (if authenticated)
        $response = $this->postJson('/api/optimize/multi-stop', $payload);

        // If authenticated, should return 200. If not, should return 401 but not 422 for waypoint count
        // We're just checking validation doesn't reject 7 waypoints
        $this->assertNotEqual(
            422,
            $response->status(),
            'Should not return 422 validation error for 7 waypoints'
        );
    }

    /**
     * Test that 8 waypoints is rejected
     */
    public function test_eight_waypoints_rejected(): void
    {
        $payload = [
            'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
            'destination' => ['lat' => 28.5355, 'lng' => 77.3910],
            'waypoints' => [
                ['lat' => 28.5400, 'lng' => 77.3000],
                ['lat' => 28.5500, 'lng' => 77.3100],
                ['lat' => 28.5600, 'lng' => 77.3200],
                ['lat' => 28.5700, 'lng' => 77.3300],
                ['lat' => 28.5800, 'lng' => 77.3400],
                ['lat' => 28.5900, 'lng' => 77.3500],
                ['lat' => 28.6000, 'lng' => 77.3600],
                ['lat' => 28.6100, 'lng' => 77.3700], // 8th waypoint
            ],
            'optimize_for' => 'distance',
        ];

        $response = $this->postJson('/api/optimize/multi-stop', $payload);

        // Should return 422 validation error
        $this->assertEquals(422, $response->status(), 'Should return validation error for 8 waypoints');

        // Should include waypoint error
        $response->assertJsonPath('message', 'Validation failed');
        $response->assertJsonStructure(['success', 'message', 'errors', 'status']);

        // Errors should mention waypoints
        $errors = $response->json('errors');
        $this->assertArrayHasKey('waypoints', $errors, 'Should have waypoints error');
    }

    /**
     * Test that 10 waypoints is rejected
     */
    public function test_ten_waypoints_rejected(): void
    {
        $waypoints = [];
        for ($i = 0; $i < 10; $i++) {
            $waypoints[] = ['lat' => 28.54 + ($i * 0.01), 'lng' => 77.30 + ($i * 0.01)];
        }

        $payload = [
            'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
            'destination' => ['lat' => 28.5355, 'lng' => 77.3910],
            'waypoints' => $waypoints,
        ];

        $response = $this->postJson('/api/optimize/multi-stop', $payload);

        $this->assertEquals(422, $response->status());
        $this->assertFalse($response->json('success'));
    }

    /**
     * Test error message for exceeding waypoints
     */
    public function test_waypoint_limit_error_message(): void
    {
        $waypoints = [];
        for ($i = 0; $i < 8; $i++) {
            $waypoints[] = ['lat' => 28.54 + ($i * 0.01), 'lng' => 77.30 + ($i * 0.01)];
        }

        $payload = [
            'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
            'destination' => ['lat' => 28.5355, 'lng' => 77.3910],
            'waypoints' => $waypoints,
        ];

        $response = $this->postJson('/api/optimize/multi-stop', $payload);

        $this->assertEquals(422, $response->status());

        $errors = $response->json('errors');
        $this->assertArrayHasKey('waypoints', $errors);

        // Error message should mention "maximum" or "7"
        $waypointError = $errors['waypoints'][0] ?? '';
        $this->assertTrue(
            str_contains($waypointError, 'maximum') || str_contains($waypointError, '7') || str_contains($waypointError, 'Maximum'),
            "Error message should mention maximum or 7. Got: $waypointError"
        );
    }

    /**
     * Test that 0 waypoints is rejected
     */
    public function test_zero_waypoints_rejected(): void
    {
        $payload = [
            'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
            'destination' => ['lat' => 28.5355, 'lng' => 77.3910],
            'waypoints' => [],
        ];

        $response = $this->postJson('/api/optimize/multi-stop', $payload);

        $this->assertEquals(422, $response->status());
        $response->assertJsonPath('errors.waypoints.0', fn($value) => str_contains($value, 'least'));
    }

    /**
     * Test waypoint coordinates validation
     */
    public function test_waypoint_coordinates_must_be_valid(): void
    {
        $payload = [
            'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
            'destination' => ['lat' => 28.5355, 'lng' => 77.3910],
            'waypoints' => [
                ['lat' => 28.5400, 'lng' => 77.3000],
                ['lat' => 'invalid', 'lng' => 77.3100], // Invalid latitude
            ],
        ];

        $response = $this->postJson('/api/optimize/multi-stop', $payload);

        $this->assertEquals(422, $response->status());
        $errors = $response->json('errors');
        $this->assertArrayHasKey('waypoints.1.lat', $errors);
    }

    /**
     * Test origin and destination are required
     */
    public function test_origin_destination_required(): void
    {
        $payload = [
            'waypoints' => [
                ['lat' => 28.5400, 'lng' => 77.3000],
            ],
        ];

        $response = $this->postJson('/api/optimize/multi-stop', $payload);

        $this->assertEquals(422, $response->status());
        $errors = $response->json('errors');

        $this->assertArrayHasKey('origin', $errors);
        $this->assertArrayHasKey('destination', $errors);
    }

    /**
     * Test latitude bounds validation
     */
    public function test_latitude_bounds_validation(): void
    {
        $payload = [
            'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
            'destination' => ['lat' => 28.5355, 'lng' => 77.3910],
            'waypoints' => [
                ['lat' => 91.0, 'lng' => 77.3000], // Invalid latitude (>90)
            ],
        ];

        $response = $this->postJson('/api/optimize/multi-stop', $payload);

        $this->assertEquals(422, $response->status());
        $errors = $response->json('errors');
        $this->assertArrayHasKey('waypoints.0.lat', $errors);
    }

    /**
     * Test longitude bounds validation
     */
    public function test_longitude_bounds_validation(): void
    {
        $payload = [
            'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
            'destination' => ['lat' => 28.5355, 'lng' => 77.3910],
            'waypoints' => [
                ['lat' => 28.5400, 'lng' => 181.0], // Invalid longitude (>180)
            ],
        ];

        $response = $this->postJson('/api/optimize/multi-stop', $payload);

        $this->assertEquals(422, $response->status());
    }

    /**
     * Test optimize_for parameter is nullable
     */
    public function test_optimize_for_is_nullable(): void
    {
        $payload = [
            'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
            'destination' => ['lat' => 28.5355, 'lng' => 77.3910],
            'waypoints' => [
                ['lat' => 28.5400, 'lng' => 77.3000],
            ],
            'optimize_for' => null,
        ];

        $response = $this->postJson('/api/optimize/multi-stop', $payload);

        // Should not fail validation because optimize_for is nullable
        // May fail with 401 if not authenticated, but not 422 for optimize_for
        $this->assertNotEqual(
            422,
            $response->status(),
            'Should not return validation error for null optimize_for'
        );
    }

    /**
     * Test invalid optimize_for value
     */
    public function test_invalid_optimize_for_rejected(): void
    {
        $payload = [
            'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
            'destination' => ['lat' => 28.5355, 'lng' => 77.3910],
            'waypoints' => [
                ['lat' => 28.5400, 'lng' => 77.3000],
            ],
            'optimize_for' => 'invalid',
        ];

        $response = $this->postJson('/api/optimize/multi-stop', $payload);

        $this->assertEquals(422, $response->status());
        $errors = $response->json('errors');
        $this->assertArrayHasKey('optimize_for', $errors);
    }
}

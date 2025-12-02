<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptimizeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'phone' => '9876543210',
        ]);

        $this->token = auth('api')->claims(['sub' => $this->user->id])->attempt([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
    }

    public function test_can_optimize_3_waypoint_route()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/optimize/multi-stop', [
                'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
                'destination' => ['lat' => 28.5244, 'lng' => 77.1855],
                'waypoints' => [
                    ['lat' => 28.6142, 'lng' => 77.2100],
                    ['lat' => 28.5500, 'lng' => 77.2000],
                ],
                'optimize_for' => 'distance',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'ordered_waypoints',
                    'total_distance_m',
                    'total_duration_s',
                    'explanation',
                ],
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data['ordered_waypoints']);
        $this->assertGreaterThan(0, $data['total_distance_m']);
        $this->assertGreaterThan(0, $data['total_duration_s']);
    }

    public function test_can_optimize_7_waypoint_route()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/optimize/multi-stop', [
                'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
                'destination' => ['lat' => 28.5244, 'lng' => 77.1855],
                'waypoints' => [
                    ['lat' => 28.6142, 'lng' => 77.2100],
                    ['lat' => 28.5500, 'lng' => 77.2000],
                    ['lat' => 28.5100, 'lng' => 77.2500],
                    ['lat' => 28.5700, 'lng' => 77.1500],
                    ['lat' => 28.6300, 'lng' => 77.1900],
                    ['lat' => 28.5600, 'lng' => 77.2200],
                    ['lat' => 28.5900, 'lng' => 77.1700],
                ],
                'optimize_for' => 'distance',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'ordered_waypoints',
                    'total_distance_m',
                    'total_duration_s',
                    'explanation',
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(7, $data['ordered_waypoints']);
    }

    public function test_optimize_fails_without_authentication()
    {
        $response = $this->postJson('/api/optimize/multi-stop', [
            'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
            'destination' => ['lat' => 28.5244, 'lng' => 77.1855],
            'waypoints' => [],
            'optimize_for' => 'distance',
        ]);

        $response->assertStatus(401);
    }

    public function test_optimize_fails_with_empty_waypoints()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/optimize/multi-stop', [
                'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
                'destination' => ['lat' => 28.5244, 'lng' => 77.1855],
                'waypoints' => [],
                'optimize_for' => 'distance',
            ]);

        $response->assertStatus(400);
    }

    public function test_optimize_with_time_preference()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/optimize/multi-stop', [
                'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
                'destination' => ['lat' => 28.5244, 'lng' => 77.1855],
                'waypoints' => [
                    ['lat' => 28.6142, 'lng' => 77.2100],
                    ['lat' => 28.5500, 'lng' => 77.2000],
                ],
                'optimize_for' => 'time',
            ]);

        $response->assertStatus(200);
    }
}

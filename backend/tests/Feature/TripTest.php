<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripTest extends TestCase
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

    public function test_can_save_trip()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/trips', [
                'name' => 'Delhi to Agra',
                'origin' => ['lat' => 28.6139, 'lng' => 77.2090, 'name' => 'Delhi'],
                'destination' => ['lat' => 27.1767, 'lng' => 78.0081, 'name' => 'Agra'],
                'waypoints' => [],
                'polyline' => '_p~iF~ps|U_ulLnnqC_mqNvxq`@',
                'distance_meters' => 206000,
                'duration_seconds' => 14400,
                'mode' => 'car',
                'cost' => [
                    [
                        'mode' => 'car',
                        'total_cost' => 2500,
                        'breakdown' => [],
                    ],
                ],
                'passengers' => 2,
                'saved_preferences' => [],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => ['id', 'name', 'user_id', 'mode', 'distance_km', 'duration_minutes'],
            ]);

        $this->assertDatabaseHas('trips', [
            'user_id' => $this->user->id,
            'name' => 'Delhi to Agra',
        ]);
    }

    public function test_can_list_trips()
    {
        Trip::create([
            'user_id' => $this->user->id,
            'name' => 'Test Trip',
            'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
            'destination' => ['lat' => 27.1767, 'lng' => 78.0081],
            'waypoints' => [],
            'polyline' => 'test',
            'distance_meters' => 206000,
            'duration_seconds' => 14400,
            'mode' => 'car',
            'cost' => [],
            'passengers' => 2,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/trips');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => ['id', 'name', 'mode', 'distance_km'],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_can_get_specific_trip()
    {
        $trip = Trip::create([
            'user_id' => $this->user->id,
            'name' => 'Test Trip',
            'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
            'destination' => ['lat' => 27.1767, 'lng' => 78.0081],
            'waypoints' => [],
            'polyline' => 'test',
            'distance_meters' => 206000,
            'duration_seconds' => 14400,
            'mode' => 'car',
            'cost' => [],
            'passengers' => 2,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson("/api/trips/{$trip->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => ['id' => $trip->id, 'name' => 'Test Trip'],
            ]);
    }

    public function test_can_delete_trip()
    {
        $trip = Trip::create([
            'user_id' => $this->user->id,
            'name' => 'Test Trip',
            'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
            'destination' => ['lat' => 27.1767, 'lng' => 78.0081],
            'waypoints' => [],
            'polyline' => 'test',
            'distance_meters' => 206000,
            'duration_seconds' => 14400,
            'mode' => 'car',
            'cost' => [],
            'passengers' => 2,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->deleteJson("/api/trips/{$trip->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('trips', ['id' => $trip->id]);
    }

    public function test_cannot_access_others_trip()
    {
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password123'),
            'phone' => '9876543210',
        ]);

        $trip = Trip::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Trip',
            'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
            'destination' => ['lat' => 27.1767, 'lng' => 78.0081],
            'waypoints' => [],
            'polyline' => 'test',
            'distance_meters' => 206000,
            'duration_seconds' => 14400,
            'mode' => 'car',
            'cost' => [],
            'passengers' => 2,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson("/api/trips/{$trip->id}");

        $response->assertStatus(403);
    }

    public function test_trip_saves_with_correct_km_conversion()
    {
        $trip = Trip::create([
            'user_id' => $this->user->id,
            'name' => 'Test Trip',
            'origin' => ['lat' => 28.6139, 'lng' => 77.2090],
            'destination' => ['lat' => 27.1767, 'lng' => 78.0081],
            'waypoints' => [],
            'polyline' => 'test',
            'distance_meters' => 206000,
            'duration_seconds' => 14400,
            'mode' => 'car',
            'cost' => [],
            'passengers' => 2,
        ]);

        $this->assertEquals(206, $trip->distance_km);
        $this->assertEquals(240, $trip->duration_minutes);
    }
}

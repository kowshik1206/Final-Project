<?php

namespace Tests\Feature;

use App\Models\Poi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoiTest extends TestCase
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

    public function test_can_list_pois()
    {
        Poi::create([
            'name' => 'Test Fuel Station',
            'type' => 'fuel',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'address' => 'Test Address',
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/pois');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
                'pagination',
            ]);
    }

    public function test_can_create_poi()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/pois', [
                'name' => 'EV Charger Station',
                'type' => 'charger',
                'latitude' => 28.6139,
                'longitude' => 77.2090,
                'address' => 'Test Address',
                'tags' => ['fast-charging', 'tesla'],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => ['id', 'name', 'type', 'latitude', 'longitude'],
            ]);

        $this->assertDatabaseHas('pois', ['name' => 'EV Charger Station']);
    }

    public function test_can_update_poi()
    {
        $poi = Poi::create([
            'name' => 'Test Fuel Station',
            'type' => 'fuel',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'address' => 'Test Address',
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->putJson("/api/pois/{$poi->id}", [
                'name' => 'Updated Fuel Station',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('pois', ['name' => 'Updated Fuel Station']);
    }

    public function test_can_delete_poi()
    {
        $poi = Poi::create([
            'name' => 'Test POI',
            'type' => 'fuel',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'address' => 'Test Address',
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->deleteJson("/api/pois/{$poi->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('pois', ['id' => $poi->id]);
    }

    public function test_can_filter_pois_by_type()
    {
        Poi::create([
            'name' => 'Fuel Station',
            'type' => 'fuel',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'created_by' => $this->user->id,
        ]);

        Poi::create([
            'name' => 'EV Charger',
            'type' => 'charger',
            'latitude' => 28.6200,
            'longitude' => 77.2100,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/pois?type=fuel');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertTrue(collect($data)->every(fn($poi) => $poi['type'] === 'fuel'));
    }

    public function test_can_find_pois_near_route()
    {
        // Create test POIs near a route
        Poi::create([
            'name' => 'Fuel Station',
            'type' => 'fuel',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'created_by' => $this->user->id,
        ]);

        Poi::create([
            'name' => 'Hospital',
            'type' => 'hospital',
            'latitude' => 28.5200,
            'longitude' => 77.3000,
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/pois/near-route', [
                'polyline' => '_p~iF~ps|U_ulLnnqC_mqNvxq`@', // Sample encoded polyline
                'radius_km' => 50,
                'type' => 'fuel',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
            ]);
    }

    public function test_cannot_update_others_poi()
    {
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password123'),
            'phone' => '9876543210',
        ]);

        $poi = Poi::create([
            'name' => 'Test POI',
            'type' => 'fuel',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'created_by' => $otherUser->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->putJson("/api/pois/{$poi->id}", [
                'name' => 'Hacked POI',
            ]);

        $response->assertStatus(403);
    }
}

<?php

namespace Tests\Feature;

use App\Models\CostConfig;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostTest extends TestCase
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

        Vehicle::create([
            'user_id' => $this->user->id,
            'type' => 'petrol',
            'fuel_efficiency_km_per_l' => 15,
            'fuel_price_per_l' => 95,
            'capacity_passengers' => 5,
        ]);
    }

    public function test_can_calculate_car_cost()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/cost/calculate', [
                'route' => [
                    'distance_meters' => 100000, // 100 km
                    'duration_seconds' => 7200,
                    'polyline' => 'test',
                    'points' => [],
                ],
                'vehicle_type' => 'petrol',
                'modes' => ['car'],
                'passengers' => 2,
                'options' => [],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => ['mode', 'total_cost', 'breakdown', 'assumptions', 'explanation'],
                ],
            ]);

        // 100km petrol car: fuel_cost = (100/15)*95 = ₹633.33, driver = 100*10 = ₹1000, tolls = (100/100)*5 = ₹5, tax = 5%, total ≈ ₹1720
        $costBreakdown = $response->json('data')[0];
        $this->assertGreaterThan(1600, $costBreakdown['total_cost']);
        $this->assertLessThan(1800, $costBreakdown['total_cost']);
    }

    public function test_can_calculate_multiple_mode_costs()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/cost/calculate', [
                'route' => [
                    'distance_meters' => 100000,
                    'duration_seconds' => 7200,
                    'polyline' => 'test',
                    'points' => [],
                ],
                'vehicle_type' => 'petrol',
                'modes' => ['car', 'train', 'flight'],
                'passengers' => 2,
                'options' => [],
            ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
        $this->assertEquals('car', $data[0]['mode']);
        $this->assertEquals('train', $data[1]['mode']);
        $this->assertEquals('flight', $data[2]['mode']);
    }

    public function test_can_get_recommendations()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/cost/recommend', [
                'route' => [
                    'distance_meters' => 100000,
                    'duration_seconds' => 7200,
                    'polyline' => 'test',
                    'points' => [],
                ],
                'vehicle_type' => 'petrol',
                'passengers' => 2,
                'options' => [],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'best_mode',
                    'best_score',
                    'ranking' => [
                        '*' => ['mode', 'score', 'reason'],
                    ],
                    'explanation',
                ],
            ]);

        $data = $response->json('data');
        $this->assertNotNull($data['best_mode']);
        $this->assertGreaterThanOrEqual(0, $data['best_score']);
        $this->assertLessThanOrEqual(1, $data['best_score']);
    }

    public function test_recommendation_explains_choice()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/cost/recommend', [
                'route' => [
                    'distance_meters' => 100000,
                    'duration_seconds' => 7200,
                    'polyline' => 'test',
                    'points' => [],
                ],
                'vehicle_type' => 'petrol',
                'passengers' => 2,
                'options' => [],
            ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data['explanation']);
        $this->assertLessThan(300, strlen($data['explanation']));
    }

    public function test_calculate_cost_fails_without_authentication()
    {
        $response = $this->postJson('/api/cost/calculate', [
            'route' => ['distance_meters' => 100000, 'duration_seconds' => 7200],
            'vehicle_type' => 'petrol',
            'modes' => ['car'],
            'passengers' => 2,
        ]);

        $response->assertStatus(401);
    }
}

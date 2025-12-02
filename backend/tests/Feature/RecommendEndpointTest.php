<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\CostService;

/**
 * Feature test for /api/recommend endpoint.
 * Binds a fake CostService implementation for deterministic responses.
 */
class RecommendEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Bind a fake CostService that returns deterministic costs
        $this->app->bind(CostService::class, function () {
            return new class {
                public function calculate(array $input) {
                    $mode = $input['mode'] ?? 'car';
                    switch ($mode) {
                        case 'car':
                            return ['total_cost' => 1000.0, 'duration_seconds' => 3600];
                        case 'ev':
                            return ['total_cost' => 900.0, 'duration_seconds' => 3800];
                        case 'train':
                            return ['total_cost' => 700.0, 'duration_seconds' => 4200];
                        case 'flight':
                            return ['total_cost' => 1500.0, 'duration_seconds' => 2400];
                        default:
                            return ['total_cost' => 2000.0, 'duration_seconds' => 6000];
                    }
                }
            };
        });
    }

    public function test_recommend_endpoint_returns_sorted_rankings()
    {
        $payload = [
            'route' => ['distance_meters' => 100000, 'duration_seconds' => 3600],
            'mode_preferences' => ['car','ev','train','flight'],
            'passengers' => 2
        ];

        $resp = $this->postJson('/api/recommend', $payload);
        $resp->assertStatus(200)
             ->assertJsonPath('status', 'success')
             ->assertJsonStructure(['status', 'data' => ['best_mode', 'ranking']]);

        $ranking = $resp->json('data.ranking');
        $this->assertIsArray($ranking);
        $this->assertNotEmpty($ranking);
        // ranking[0] should have highest score
        $this->assertArrayHasKey('mode', $ranking[0]);
        $this->assertArrayHasKey('score', $ranking[0]);
        $this->assertArrayHasKey('cost_snapshot', $ranking[0]);
    }
}

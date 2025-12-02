<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\CostService;

/**
 * CalcCostEndpointTest
 *
 * Tests POST /api/cost/calculate and POST /api/cost/preview endpoints.
 * Validates response structure, multi-mode comparison, and sensitivity analysis.
 */
class CalcCostEndpointTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test /api/cost/calculate returns correct response structure
     */
    public function testCalculateCostEndpointReturnsSuccessResponse(): void
    {
        $user = \App\Models\User::factory()->create();

        $payload = [
            'route' => [
                'distance_meters' => 100000,
                'duration_seconds' => 3600,
            ],
            'mode_preferences' => ['car', 'ev'],
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/cost/calculate', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'costs' => [
                        'car' => [
                            'mode',
                            'total_cost',
                            'breakdown',
                            'assumptions',
                            'explanation'
                        ],
                        'ev' => [
                            'mode',
                            'total_cost',
                            'breakdown',
                            'assumptions',
                            'explanation'
                        ]
                    ],
                    'distance_km'
                ]
            ]);

        $this->assertSame('success', $response->json('status'));
        $this->assertSame('car', $response->json('data.costs.car.mode'));
        $this->assertSame('ev', $response->json('data.costs.ev.mode'));
    }

    /**
     * Test multi-mode cost comparison
     */
    public function testMultiModeCostComparison(): void
    {
        $user = \App\Models\User::factory()->create();

        $payload = [
            'route' => [
                'distance_meters' => 250000,
            ],
            'mode_preferences' => ['car', 'ev', 'train', 'flight'],
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/cost/calculate', $payload);

        $response->assertStatus(200);

        $costs = $response->json('data.costs');

        // All modes should be present
        $this->assertArrayHasKey('car', $costs);
        $this->assertArrayHasKey('ev', $costs);
        $this->assertArrayHasKey('train', $costs);
        $this->assertArrayHasKey('flight', $costs);

        // All should have numeric costs
        $this->assertIsNumeric($costs['car']['total_cost']);
        $this->assertIsNumeric($costs['ev']['total_cost']);
        $this->assertIsNumeric($costs['train']['total_cost']);
        $this->assertIsNumeric($costs['flight']['total_cost']);
    }

    /**
     * Test explanation field is non-empty
     */
    public function testExplanationFieldIsPopulated(): void
    {
        $user = \App\Models\User::factory()->create();

        $payload = [
            'route' => [
                'distance_meters' => 150000,
            ],
            'mode_preferences' => ['car'],
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/cost/calculate', $payload);

        $response->assertStatus(200);

        $explanation = $response->json('data.costs.car.explanation');
        $this->assertIsString($explanation);
        $this->assertNotEmpty($explanation);
        $this->assertStringContainsString('₹', $explanation); // Should contain currency
    }

    /**
     * Test /api/cost/preview endpoint with sensitivity analysis
     */
    public function testPreviewCostEndpointWithSensitivityAnalysis(): void
    {
        $user = \App\Models\User::factory()->create();

        $payload = [
            'route' => [
                'distance_meters' => 100000,
            ],
            'mode_preferences' => ['car'],
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/cost/preview', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'costs',
                    'sensitivity' => [
                        'fuel_price_effect' => [
                            'plus_5_percent',
                            'minus_5_percent'
                        ],
                        'efficiency_effect' => [
                            'plus_10_percent',
                            'minus_10_percent'
                        ]
                    ],
                    'distance_km'
                ]
            ]);

        // Verify sensitivity is numeric
        $sensitivity = $response->json('data.sensitivity');
        $this->assertIsNumeric($sensitivity['fuel_price_effect']['plus_5_percent']);
        $this->assertIsNumeric($sensitivity['fuel_price_effect']['minus_5_percent']);
        $this->assertIsNumeric($sensitivity['efficiency_effect']['plus_10_percent']);
        $this->assertIsNumeric($sensitivity['efficiency_effect']['minus_10_percent']);
    }

    /**
     * Test validation error when distance_meters missing
     */
    public function testValidationErrorMissingDistance(): void
    {
        $user = \App\Models\User::factory()->create();

        $payload = [
            'route' => [],
            'mode_preferences' => ['car'],
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/cost/calculate', $payload);

        $response->assertStatus(422);
    }

    /**
     * Test validation error when mode_preferences empty
     */
    public function testValidationErrorEmptyModes(): void
    {
        $user = \App\Models\User::factory()->create();

        $payload = [
            'route' => [
                'distance_meters' => 100000,
            ],
            'mode_preferences' => [],
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/cost/calculate', $payload);

        $response->assertStatus(422);
    }

    /**
     * Test with custom vehicle parameters
     */
    public function testWithCustomVehicleParameters(): void
    {
        $user = \App\Models\User::factory()->create();

        $payload = [
            'route' => [
                'distance_meters' => 100000,
            ],
            'mode_preferences' => ['car'],
            'vehicle' => [
                'fuel_efficiency_km_per_l' => 20,
                'fuel_price_per_l' => 100,
                'driver_cost_per_km' => 15,
            ],
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/cost/calculate', $payload);

        $response->assertStatus(200);

        $car = $response->json('data.costs.car');
        
        // With custom params, cost should differ from defaults
        // fuel_needed = 100 / 20 = 5
        // fuel_cost = 5 * 100 = 500
        // driver_cost = 100 * 15 = 1500
        // toll = 500 * 0.05 = 25
        // total = 500 + 1500 + 25 = 2025.00
        
        $this->assertSame(2025.00, $car['total_cost']);
        $this->assertSame(500.00, $car['breakdown']['fuel_cost']);
        $this->assertSame(1500.00, $car['breakdown']['driver_cost']);
    }
}

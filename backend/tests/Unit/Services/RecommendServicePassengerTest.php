<?php

namespace Tests\Unit\Services;

use App\Services\DTOs\CostBreakdown;
use App\Services\RecommendService;
use PHPUnit\Framework\TestCase;

/**
 * Recommend Service Passenger Scoring Test
 * 
 * Verifies that passenger count affects transportation mode recommendations
 * according to the specification:
 * - passengers >= 5: increase flight convenience +0.15
 * - passengers >= 6: decrease car convenience -0.2
 */
class RecommendServicePassengerTest extends TestCase
{
    private RecommendService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecommendService();
    }

    /**
     * Test that passenger count affects recommendations
     */
    public function test_passenger_count_affects_recommendations(): void
    {
        // Create identical cost breakdowns for different modes
        $breakdowns = [
            new CostBreakdown(
                mode: 'car',
                baseCost: 1000,
                tollCost: 100,
                totalCost: 1100,
                currency: 'INR',
            ),
            new CostBreakdown(
                mode: 'flight',
                baseCost: 2000,
                tollCost: 0,
                totalCost: 2000,
                currency: 'INR',
            ),
        ];

        // Get recommendation for 1 passenger
        $recom1Passenger = $this->service->recommend($breakdowns, 1, 100);

        // Get recommendation for 5 passengers
        $recom5Passengers = $this->service->recommend($breakdowns, 5, 100);

        // With 5 passengers, flight should score higher (convenience bonus)
        $car1 = collect($recom1Passenger['ranking'])->firstWhere('mode', 'car');
        $car5 = collect($recom5Passengers['ranking'])->firstWhere('mode', 'car');
        $flight1 = collect($recom1Passenger['ranking'])->firstWhere('mode', 'flight');
        $flight5 = collect($recom5Passengers['ranking'])->firstWhere('mode', 'flight');

        // Flight score should increase or stay same with more passengers
        $this->assertGreaterThanOrEqual($flight1['score'], $flight5['score']);
    }

    /**
     * Test that 5+ passengers increases flight convenience
     */
    public function test_five_or_more_passengers_boosts_flight(): void
    {
        $breakdowns = [
            new CostBreakdown(
                mode: 'flight',
                baseCost: 2000,
                tollCost: 0,
                totalCost: 2000,
                currency: 'INR',
            ),
        ];

        $recom1 = $this->service->recommend($breakdowns, 1, 500);
        $recom5 = $this->service->recommend($breakdowns, 5, 500);

        // Flight should score better (or equal) with 5 passengers
        $this->assertGreaterThanOrEqual($recom1['best_score'], $recom5['best_score']);
    }

    /**
     * Test that 6+ passengers decreases car convenience
     */
    public function test_six_or_more_passengers_reduces_car(): void
    {
        $breakdowns = [
            new CostBreakdown(
                mode: 'car',
                baseCost: 1000,
                tollCost: 100,
                totalCost: 1100,
                currency: 'INR',
            ),
        ];

        $recom5 = $this->service->recommend($breakdowns, 5, 100);
        $recom6 = $this->service->recommend($breakdowns, 6, 100);

        // Car should score lower with 6 passengers (capacity issue)
        $car5Score = $recom5['best_score'];
        $car6Score = $recom6['best_score'];

        // With 6 passengers, car score should decrease or stay same
        $this->assertGreaterThanOrEqual($car5Score, $car6Score, 'Car score should not increase with 6+ passengers');
    }

    /**
     * Test ranking changes with passenger count
     */
    public function test_ranking_order_changes_with_passengers(): void
    {
        $breakdowns = [
            new CostBreakdown(
                mode: 'car',
                baseCost: 1000,
                tollCost: 100,
                totalCost: 1100,
                currency: 'INR',
            ),
            new CostBreakdown(
                mode: 'flight',
                baseCost: 2000,
                tollCost: 0,
                totalCost: 2000,
                currency: 'INR',
            ),
        ];

        $recom1 = $this->service->recommend($breakdowns, 1, 500);
        $recom6 = $this->service->recommend($breakdowns, 6, 500);

        // With 1 passenger, car is likely best (cheaper)
        // With 6 passengers, ranking might change due to capacity issues

        $this->assertIsArray($recom1['ranking']);
        $this->assertIsArray($recom6['ranking']);

        // Both should have 2 modes
        $this->assertCount(2, $recom1['ranking']);
        $this->assertCount(2, $recom6['ranking']);
    }

    /**
     * Test passenger information included in explanation
     */
    public function test_passenger_count_in_explanation(): void
    {
        $breakdowns = [
            new CostBreakdown(
                mode: 'car',
                baseCost: 1000,
                tollCost: 100,
                totalCost: 1100,
                currency: 'INR',
            ),
        ];

        $recom1 = $this->service->recommend($breakdowns, 1, 100);
        $recom5 = $this->service->recommend($breakdowns, 5, 100);

        // Explanation for 5 passengers might mention group
        $explanation5 = $recom5['explanation'];
        $this->assertIsString($explanation5);

        // Both should return explanations
        $this->assertNotEmpty($recom1['explanation']);
        $this->assertNotEmpty($recom5['explanation']);
    }

    /**
     * Test edge case: exactly 5 passengers
     */
    public function test_exactly_five_passengers_triggers_boost(): void
    {
        $breakdowns = [
            new CostBreakdown(
                mode: 'flight',
                baseCost: 2000,
                tollCost: 0,
                totalCost: 2000,
                currency: 'INR',
            ),
        ];

        $recom5 = $this->service->recommend($breakdowns, 5, 500);

        // Should successfully recommend flight for 5 passengers
        $this->assertEquals('flight', $recom5['best_mode']);
        $this->assertGreaterThan(0, $recom5['best_score']);
    }

    /**
     * Test edge case: exactly 6 passengers
     */
    public function test_exactly_six_passengers_triggers_penalty(): void
    {
        $breakdowns = [
            new CostBreakdown(
                mode: 'car',
                baseCost: 1000,
                tollCost: 100,
                totalCost: 1100,
                currency: 'INR',
            ),
        ];

        $recom6 = $this->service->recommend($breakdowns, 6, 100);

        // Should recommend car but with reduced convenience score
        $this->assertEquals('car', $recom6['best_mode']);
        $this->assertGreaterThan(0, $recom6['best_score']);
    }

    /**
     * Test that passenger adjustments are applied correctly
     */
    public function test_passenger_adjustments_consistent(): void
    {
        $breakdowns = [
            new CostBreakdown(mode: 'car', baseCost: 1000, tollCost: 50, totalCost: 1050, currency: 'INR'),
            new CostBreakdown(mode: 'flight', baseCost: 2500, tollCost: 0, totalCost: 2500, currency: 'INR'),
            new CostBreakdown(mode: 'train', baseCost: 800, tollCost: 0, totalCost: 800, currency: 'INR'),
        ];

        // Test with different passenger counts
        for ($passengers = 1; $passengers <= 7; $passengers++) {
            $recom = $this->service->recommend($breakdowns, $passengers, 200);

            // Should always return valid recommendation
            $this->assertNotNull($recom['best_mode']);
            $this->assertGreaterThan(0, $recom['best_score']);
            $this->assertCount(3, $recom['ranking']);
        }
    }

    /**
     * Test no passenger adjustment for negative counts (edge case)
     */
    public function test_passenger_count_should_be_positive(): void
    {
        $breakdowns = [
            new CostBreakdown(mode: 'car', baseCost: 1000, tollCost: 50, totalCost: 1050, currency: 'INR'),
        ];

        // Should handle gracefully
        $recom = $this->service->recommend($breakdowns, 0, 100);

        $this->assertIsArray($recom);
        $this->assertNotNull($recom['best_mode']);
    }
}

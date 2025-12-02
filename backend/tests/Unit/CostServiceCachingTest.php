<?php

namespace Tests\Unit;

use App\Services\CostService;
use App\Services\CostConfigService;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Cache;

/**
 * CostServiceCachingTest
 *
 * Verifies cache HIT/MISS behavior for cost calculations.
 */
class CostServiceCachingTest extends TestCase
{
    protected CostService $service;
    protected CostConfigService $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new CostConfigService();
        $this->service = new CostService($this->config);
        Cache::flush();
    }

    /**
     * Test first calculation returns cached=false, second returns cached=true
     */
    public function testCostCalculationCaching(): void
    {
        $input = [
            'distance_meters' => 100000,
            'mode_preferences' => ['car'],
        ];

        // First call: not cached
        $result1 = $this->service->calculate($input);
        $this->assertFalse($result1['cached'], 'First call should not be cached');
        $this->assertIsArray($result1['results']);
        $this->assertArrayHasKey('car', $result1['results']);

        // Second identical call: should be cached
        $result2 = $this->service->calculate($input);
        $this->assertTrue($result2['cached'], 'Second identical call should be cached');

        // Results should be identical
        $this->assertSame($result1['results'], $result2['results']);
    }

    /**
     * Test cache MISS when input changes
     */
    public function testCacheMissOnInputChange(): void
    {
        $input1 = ['distance_meters' => 100000, 'mode_preferences' => ['car']];
        $input2 = ['distance_meters' => 200000, 'mode_preferences' => ['car']];

        // First input: not cached
        $result1 = $this->service->calculate($input1);
        $this->assertFalse($result1['cached']);

        // Different input: not cached (different hash)
        $result2 = $this->service->calculate($input2);
        $this->assertFalse($result2['cached']);

        // Results should be different (different distances)
        $this->assertNotSame(
            $result1['results']['car']['total_cost'],
            $result2['results']['car']['total_cost']
        );
    }

    /**
     * Test multi-mode caching
     */
    public function testMultiModeCaching(): void
    {
        $input = [
            'distance_meters' => 250000,
            'mode_preferences' => ['car', 'ev', 'train', 'flight'],
        ];

        // First call
        $result1 = $this->service->calculate($input);
        $this->assertFalse($result1['cached']);
        $this->assertArrayHasKey('car', $result1['results']);
        $this->assertArrayHasKey('ev', $result1['results']);
        $this->assertArrayHasKey('train', $result1['results']);
        $this->assertArrayHasKey('flight', $result1['results']);

        // Second call: cached
        $result2 = $this->service->calculate($input);
        $this->assertTrue($result2['cached']);
        $this->assertSame($result1['results'], $result2['results']);
    }
}

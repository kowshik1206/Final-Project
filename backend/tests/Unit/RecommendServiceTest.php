<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\RecommendService;
use App\Services\CostConfigService;

/**
 * Unit test for RecommendService scoring logic.
 *
 * This test uses a small fake CostService injected via a local class.
 */
class RecommendServiceTest extends TestCase
{
    public function test_passenger_effect_switches_best_mode()
    {
        // Fake CostService that returns controlled costs and durations
        $fakeCostService = new class {
            public function calculate(array $input) {
                // Return different cost for modes to create tie-breaking scenario
                $mode = $input['mode'];
                if ($mode === 'car') {
                    return ['total_cost' => 1000.0, 'duration_seconds' => 3600];
                } elseif ($mode === 'flight') {
                    return ['total_cost' => 1000.0, 'duration_seconds' => 3600];
                } else {
                    return ['total_cost' => 1500.0, 'duration_seconds' => 5400];
                }
            }
        };

        $fakeConfig = new class {
            public function get($key, $default = null) {
                // default weights
                return $default;
            }
        };

        $svc = new RecommendService($fakeCostService, $fakeConfig);

        // Case 1: 2 passengers -> car should win
        $input = [
            'route' => ['distance_meters' => 100000, 'duration_seconds' => 3600],
            'mode_preferences' => ['car','flight'],
            'passengers' => 2
        ];
        $res = $svc->recommend($input);
        $this->assertEquals('car', $res['best_mode']);

        // Case 2: 6 passengers -> flight should win due to +0.15 convenience and car -0.2
        $input['passengers'] = 6;
        $res2 = $svc->recommend($input);
        $this->assertEquals('flight', $res2['best_mode']);
    }

    public function test_scores_are_normalized_and_bounded()
    {
        $fakeCostService = new class {
            public function calculate(array $input) {
                $mode = $input['mode'];
                // different costs and times
                if ($mode === 'car') {
                    return ['total_cost' => 500.0, 'duration_seconds' => 3000];
                } elseif ($mode === 'ev') {
                    return ['total_cost' => 600.0, 'duration_seconds' => 3200];
                } else {
                    return ['total_cost' => 1200.0, 'duration_seconds' => 5000];
                }
            }
        };
        $svc = new RecommendService($fakeCostService, new class { public function get($k,$d=null){return $d;} });

        $input = [
            'route' => ['distance_meters' => 50000, 'duration_seconds' => 3000],
            'mode_preferences' => ['car','ev','train'],
            'passengers' => 1
        ];
        $out = $svc->recommend($input);
        $this->assertArrayHasKey('ranking', $out);
        foreach ($out['ranking'] as $item) {
            $this->assertGreaterThanOrEqual(0.0, $item['score']);
            $this->assertLessThanOrEqual(1.0, $item['score']);
            $this->assertIsString($item['explanation']);
            $this->assertArrayHasKey('cost_snapshot', $item);
        }
    }
}

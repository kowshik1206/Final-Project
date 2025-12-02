<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * RecommendService
 *
 * Produces ranked travel mode recommendations based on cost/time/convenience.
 *
 * Public API:
 *   public function recommend(array $input): array
 *
 * Input structure:
 *  - route: ['distance_meters'=>int, 'duration_seconds'=>int, 'polyline'=>string|null]
 *  - mode_preferences: array of modes e.g. ['car','ev','train','flight']
 *  - passengers: int
 *  - preferences: optional array
 *
 * Output: [
 *   'ranking' => [
 *     ['mode'=>string,'score'=>float,'explanation'=>string,'cost_snapshot'=>array],
 *     ...
 *   ],
 *   'best_mode' => string
 * ]
 */
class RecommendService
{
    protected CostService $costService;
    protected CostConfigService $config;

    public function __construct(CostService $costService, CostConfigService $config)
    {
        $this->costService = $costService;
        $this->config = $config;
    }

    /**
     * Main entrypoint.
     *
     * @param array $input
     * @return array
     */
    public function recommend(array $input): array
    {
        $modes = $input['mode_preferences'] ?? [];
        if (empty($modes) || !is_array($modes)) {
            throw new InvalidArgumentException('mode_preferences is required and must be an array');
        }

        $passengers = isset($input['passengers']) ? (int)$input['passengers'] : 1;
        $route = $input['route'] ?? ['distance_meters' => 0, 'duration_seconds' => 0];

        // Fetch weights from config (fall back to defaults)
        $wCost = (float) $this->config->get('recommend_weight_cost', 0.5);
        $wTime = (float) $this->config->get('recommend_weight_time', 0.3);
        $wConv = (float) $this->config->get('recommend_weight_convenience', 0.2);

        // Collect mode results (cost + time + convenience base)
        $results = [];
        foreach ($modes as $mode) {
            // Request a cost snapshot for this mode — CostService returns single-mode result
            $modeInput = [
                'route' => $route,
                'vehicle' => $input['vehicle'] ?? [],
                'mode' => $mode,
                'passengers' => $passengers
            ];

            $costRes = $this->costService->calculate($modeInput);
            // Expect keys: total_cost, duration_seconds (in cost result duration may be missing — use route duration)
            $totalCost = isset($costRes['total_cost']) ? (float)$costRes['total_cost'] : 0.0;
            $duration = isset($costRes['duration_seconds']) ? (int)$costRes['duration_seconds'] : (int)$route['duration_seconds'];

            $convenience = $this->modeConvenienceBase($mode);

            // passenger-based adjustments
            if ($passengers >= 5 && $mode === 'flight') {
                $convenience = min(1.0, $convenience + 0.15);
            }
            if ($passengers >= 6 && $mode === 'car') {
                $convenience = max(0.0, $convenience - 0.2);
            }

            $results[] = [
                'mode' => $mode,
                'total_cost' => round($totalCost, 2),
                'duration_seconds' => $duration,
                'convenience' => round($convenience, 3),
                'cost_snapshot' => $costRes,
            ];
        }

        if (empty($results)) {
            throw new InvalidArgumentException('No valid mode results computed');
        }

        // Normalize cost & time
        $costs = array_column($results, 'total_cost');
        $times = array_column($results, 'duration_seconds');

        $minCost = min($costs);
        $maxCost = max($costs);
        $minTime = min($times);
        $maxTime = max($times);

        $ranking = [];
        foreach ($results as $r) {
            // cost normalization (0..1) where 0 = cheapest, 1 = most expensive
            $costNorm = ($maxCost - $minCost) > 0 ? (($r['total_cost'] - $minCost) / ($maxCost - $minCost)) : 0.0;
            $timeNorm = ($maxTime - $minTime) > 0 ? (($r['duration_seconds'] - $minTime) / ($maxTime - $minTime)) : 0.0;

            // Score = wCost*(1 - costNorm) + wTime*(1 - timeNorm) + wConv * convenience
            $score = $wCost * (1.0 - $costNorm) + $wTime * (1.0 - $timeNorm) + $wConv * $r['convenience'];
            $score = max(0.0, min(1.0, $score)); // clamp
            $scoreRounded = round($score, 4);

            // Explanation: cheapest delta and time delta relative to cheapest/time-min
            $cheapestDelta = round($r['total_cost'] - $minCost, 2);
            $timeDeltaMinutes = (int) round(($r['duration_seconds'] - $minTime) / 60.0);

            $explanationParts = [];
            if ($cheapestDelta === 0.0) {
                $explanationParts[] = "Cheapest";
            } else {
                $explanationParts[] = "₹" . number_format($cheapestDelta, 2) . " more than cheapest";
            }

            if ($timeDeltaMinutes === 0) {
                $explanationParts[] = "Fastest";
            } else {
                $explanationParts[] = "{$timeDeltaMinutes}m slower than fastest";
            }

            $explanationParts[] = "Convenience score: " . number_format($r['convenience'], 3);

            $explanation = implode('; ', $explanationParts);

            $ranking[] = [
                'mode' => $r['mode'],
                'score' => $scoreRounded,
                'explanation' => $explanation,
                'cost_snapshot' => $r['cost_snapshot']
            ];
        }

        // sort by score desc
        usort($ranking, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return [
            'best_mode' => $ranking[0]['mode'],
            'ranking' => $ranking
        ];
    }

    protected function modeConvenienceBase(string $mode): float
    {
        return match ($mode) {
            'car' => 1.0,
            'ev' => 0.8,
            'train' => 0.6,
            'flight' => 0.4,
            default => 0.5
        };
    }
}

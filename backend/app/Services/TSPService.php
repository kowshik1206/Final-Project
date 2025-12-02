<?php

namespace App\Services;

use App\Services\Maps\MapServiceInterface;

class TSPService
{
    private MapServiceInterface $mapService;
    private const MAX_WAYPOINTS = 7;

    public function __construct(MapServiceInterface $mapService)
    {
        $this->mapService = $mapService;
    }

    /**
     * Optimize waypoints order using TSP algorithm
     */
    public function optimize(
        array $origin,
        array $destination,
        array $waypoints,
        string $optimizeFor = 'distance'
    ): array {
        // Validate inputs
        if (empty($origin) || empty($destination)) {
            throw new \Exception('Origin and destination required');
        }

        // Deduplicate waypoints
        $waypoints = $this->deduplicateWaypoints($waypoints);

        // Handle too many waypoints
        if (count($waypoints) > self::MAX_WAYPOINTS) {
            return $this->enRouteOptimization($origin, $destination, $waypoints, $optimizeFor);
        }

        // Run brute-force TSP for <= 7 waypoints
        return $this->bruteForceTSP($origin, $destination, $waypoints, $optimizeFor);
    }

    /**
     * Brute-force TSP - try all permutations
     */
    private function bruteForceTSP(
        array $origin,
        array $destination,
        array $waypoints,
        string $optimizeFor = 'distance'
    ): array {
        if (empty($waypoints)) {
            return $this->calculateDirectRoute($origin, $destination, $optimizeFor);
        }

        $permutations = $this->generatePermutations($waypoints);
        $bestRoute = null;
        $bestCost = PHP_FLOAT_MAX;

        foreach ($permutations as $perm) {
            $cost = $this->calculateRouteCost($origin, $destination, $perm, $optimizeFor);

            if ($cost < $bestCost) {
                $bestCost = $cost;
                $bestRoute = $perm;
            }
        }

        return $this->buildRouteResponse($origin, $destination, $bestRoute, $optimizeFor);
    }

    /**
     * En-route heuristic for >7 waypoints
     */
    private function enRouteOptimization(
        array $origin,
        array $destination,
        array $waypoints,
        string $optimizeFor = 'distance'
    ): array {
        // Sort waypoints by projection along origin->destination bearing
        $sortedWaypoints = $this->sortByProjection($origin, $destination, $waypoints);

        // Apply 2-opt improvement
        $sortedWaypoints = $this->apply2Opt($origin, $destination, $sortedWaypoints, $optimizeFor);

        return $this->buildRouteResponse($origin, $destination, $sortedWaypoints, $optimizeFor);
    }

    /**
     * Sort waypoints by projection on origin->destination line
     */
    private function sortByProjection(array $origin, array $destination, array $waypoints): array
    {
        $projections = [];

        $dLat = $destination['lat'] - $origin['lat'];
        $dLng = $destination['lng'] - $origin['lng'];
        $length = sqrt($dLat ** 2 + $dLng ** 2);

        foreach ($waypoints as $idx => $wp) {
            $pLat = $wp['lat'] - $origin['lat'];
            $pLng = $wp['lng'] - $origin['lng'];

            // Dot product projection
            $projection = ($pLat * $dLat + $pLng * $dLng) / ($length ** 2);
            $projections[$idx] = $projection;
        }

        asort($projections);
        $sorted = [];

        foreach ($projections as $idx => $_) {
            $sorted[] = $waypoints[$idx];
        }

        return $sorted;
    }

    /**
     * Apply 2-opt local optimization
     */
    private function apply2Opt(
        array $origin,
        array $destination,
        array $waypoints,
        string $optimizeFor = 'distance'
    ): array {
        $improved = true;
        $best = $waypoints;

        while ($improved) {
            $improved = false;

            for ($i = 0; $i < count($best) - 1; $i++) {
                for ($j = $i + 2; $j < count($best); $j++) {
                    $new = $best;
                    $new = array_merge(
                        array_slice($new, 0, $i + 1),
                        array_reverse(array_slice($new, $i + 1, $j - $i)),
                        array_slice($new, $j)
                    );

                    $oldCost = $this->calculateRouteCost($origin, $destination, $best, $optimizeFor);
                    $newCost = $this->calculateRouteCost($origin, $destination, $new, $optimizeFor);

                    if ($newCost < $oldCost) {
                        $best = $new;
                        $improved = true;
                    }
                }
            }
        }

        return $best;
    }

    /**
     * Calculate total cost of a route
     */
    private function calculateRouteCost(
        array $origin,
        array $destination,
        array $waypoints,
        string $optimizeFor = 'distance'
    ): float {
        $cost = 0;
        $current = $origin;

        foreach ($waypoints as $wp) {
            $distance = $this->mapService->getDistance(
                $current['lat'],
                $current['lng'],
                $wp['lat'],
                $wp['lng']
            );

            if ($optimizeFor === 'distance') {
                $cost += $distance;
            } else {
                // Estimate time (~80 km/h)
                $cost += $distance / 80;
            }

            $current = $wp;
        }

        // Add final segment to destination
        $distance = $this->mapService->getDistance(
            $current['lat'],
            $current['lng'],
            $destination['lat'],
            $destination['lng']
        );

        if ($optimizeFor === 'distance') {
            $cost += $distance;
        } else {
            $cost += $distance / 80;
        }

        return $cost;
    }

    /**
     * Direct route without waypoints
     */
    private function calculateDirectRoute(
        array $origin,
        array $destination,
        string $optimizeFor = 'distance'
    ): array {
        $distance = $this->mapService->getDistance(
            $origin['lat'],
            $origin['lng'],
            $destination['lat'],
            $destination['lng']
        );

        $duration = (int)($distance / 80 * 3600); // seconds

        return [
            'ordered_waypoints' => [],
            'total_distance_m' => (int)($distance * 1000),
            'total_duration_s' => $duration,
            'explanation' => 'Direct route from origin to destination',
        ];
    }

    /**
     * Build final route response
     */
    private function buildRouteResponse(
        array $origin,
        array $destination,
        array $waypoints,
        string $optimizeFor = 'distance'
    ): array {
        $totalCost = $this->calculateRouteCost($origin, $destination, $waypoints, $optimizeFor);

        $distance = $totalCost;
        if ($optimizeFor !== 'distance') {
            // Convert time back to distance assuming 80 km/h
            $distance = $totalCost * 80;
        }

        return [
            'ordered_waypoints' => $waypoints,
            'total_distance_m' => (int)($distance * 1000),
            'total_duration_s' => (int)(($distance / 80) * 3600),
            'explanation' => 'Optimized route for ' . count($waypoints) . ' waypoints',
        ];
    }

    /**
     * Generate all permutations of waypoints
     */
    private function generatePermutations(array $items): array
    {
        if (count($items) <= 1) {
            return [$items];
        }

        $result = [];
        $lastItem = array_pop($items);

        foreach ($this->generatePermutations($items) as $perm) {
            foreach (range(0, count($perm)) as $i) {
                $result[] = array_merge(
                    array_slice($perm, 0, $i),
                    [$lastItem],
                    array_slice($perm, $i)
                );
            }
        }

        return $result;
    }

    /**
     * Deduplicate waypoints by coordinates
     */
    private function deduplicateWaypoints(array $waypoints): array
    {
        $unique = [];
        $seen = [];

        foreach ($waypoints as $wp) {
            $key = round($wp['lat'], 6) . ',' . round($wp['lng'], 6);

            if (!isset($seen[$key])) {
                $unique[] = $wp;
                $seen[$key] = true;
            }
        }

        return $unique;
    }
}

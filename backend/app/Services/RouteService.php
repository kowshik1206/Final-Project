<?php

namespace App\Services;

use App\Models\Trip;

class RouteService
{
    /**
     * Get route between two points
     */
    public function getRoute($startLocation, $endLocation, $mode)
    {
        // This would typically call an external API like Google Maps or OSRM
        return [
            'start' => $startLocation,
            'end' => $endLocation,
            'mode' => $mode,
            'distance' => 10.5, // km
            'duration' => 20, // minutes
            'polyline' => 'encoded_polyline_here',
            'steps' => [],
        ];
    }

    /**
     * Get alternative routes
     */
    public function getAlternatives($startLocation, $endLocation, $mode)
    {
        $routes = [];

        for ($i = 0; $i < 3; $i++) {
            $routes[] = [
                'id' => $i + 1,
                'distance' => 10.5 + ($i * 0.5),
                'duration' => 20 + ($i * 5),
                'polyline' => 'encoded_polyline_here',
            ];
        }

        return $routes;
    }

    /**
     * Calculate route distance
     */
    public function calculateDistance($startLat, $startLon, $endLat, $endLon)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($endLat - $startLat);
        $dLon = deg2rad($endLon - $startLon);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($startLat)) * cos(deg2rad($endLat)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }
}

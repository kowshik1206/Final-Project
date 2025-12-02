<?php

namespace App\Services\Maps;

use App\Services\DTOs\RouteData;

class MapServiceFake implements MapServiceInterface
{
    /**
     * Returns deterministic fake route data for testing
     */
    public function calculateRoute(
        float $originLat,
        float $originLng,
        float $destinationLat,
        float $destinationLng,
        ?string $preference = 'fastest'
    ): RouteData {
        // Simple Haversine calculation for determinism
        $distanceMeters = $this->haversineDistance($originLat, $originLng, $destinationLat, $destinationLng) * 1000;
        
        // Estimate duration: ~80 km/h average
        $durationSeconds = (int)($distanceMeters / 80 * 3.6);

        return new RouteData(
            distanceMeters: $distanceMeters,
            durationSeconds: $durationSeconds,
            polyline: $this->encodePolyline([
                ['lat' => $originLat, 'lng' => $originLng],
                ['lat' => ($originLat + $destinationLat) / 2, 'lng' => ($originLng + $destinationLng) / 2],
                ['lat' => $destinationLat, 'lng' => $destinationLng],
            ]),
            points: [
                ['lat' => $originLat, 'lng' => $originLng],
                ['lat' => $destinationLat, 'lng' => $destinationLng],
            ],
            preference: $preference ?? 'fastest',
        );
    }

    public function getDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return $this->haversineDistance($lat1, $lng1, $lat2, $lng2);
    }

    public function decodePolyline(string $polyline): array
    {
        // Simple base64 decode for testing
        $decoded = base64_decode($polyline);
        if (!$decoded) {
            return [];
        }
        
        return json_decode($decoded, true) ?? [];
    }

    public function encodePolyline(array $coordinates): string
    {
        // Simple base64 encode for testing
        return base64_encode(json_encode($coordinates));
    }

    /**
     * Haversine formula for distance calculation
     */
    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}

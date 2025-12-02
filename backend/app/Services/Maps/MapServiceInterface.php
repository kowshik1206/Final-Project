<?php

namespace App\Services\Maps;

use App\Services\DTOs\RouteData;

interface MapServiceInterface
{
    /**
     * Calculate route between two points
     */
    public function calculateRoute(
        float $originLat,
        float $originLng,
        float $destinationLat,
        float $destinationLng,
        ?string $preference = 'fastest'
    ): RouteData;

    /**
     * Get distance between two coordinates in meters
     */
    public function getDistance(float $lat1, float $lng1, float $lat2, float $lng2): float;

    /**
     * Decode polyline string
     */
    public function decodePolyline(string $polyline): array;

    /**
     * Encode coordinates to polyline
     */
    public function encodePolyline(array $coordinates): string;
}

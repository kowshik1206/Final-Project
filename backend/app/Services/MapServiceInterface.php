<?php

namespace App\Services;

interface MapServiceInterface
{
    /**
     * Calculate a route given points or polyline.
     *
     * @param array|null $points Array of ['lat'=>float,'lng'=>float]
     * @param string|null $polyline Encoded polyline (optional)
     * @param string $preference 'fastest'|'shortest'|'avoid_highways'
     * @param string $mode 'car'|'ev'|'train'|'flight'
     * @return array [
     *   'polyline' => string,
     *   'distance_meters' => int,
     *   'duration_seconds' => int,
     *   'steps' => array,
     *   'calculated_via' => 'fake'|'google'|'fallback'
     * ]
     */
    public function calculateRoute(?array $points, ?string $polyline, string $preference = 'fastest', string $mode = 'car'): array;

    /**
     * Decode an encoded polyline into array of points.
     *
     * @param string $polyline
     * @return array
     */
    public function decodePolyline(string $polyline): array;

    /**
     * Encode an array of points into an encoded polyline.
     *
     * @param array $points
     * @return string
     */
    public function encodePolyline(array $points): string;
}

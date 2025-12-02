<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * MapServiceFake
 *
 * Deterministic fake map service using Haversine distances and speed assumptions.
 */
class MapServiceFake implements MapServiceInterface
{
    // speeds in km/h for modes
    protected array $speeds = [
        'car' => 70.0,
        'ev' => 70.0,
        'train' => 90.0,
        'flight' => 800.0,
    ];

    public function calculateRoute(?array $points, ?string $polyline, string $preference = 'fastest', string $mode = 'car'): array
    {
        if ($polyline !== null) {
            $points = $this->decodePolyline($polyline);
        }

        if (empty($points) || !is_array($points) || count($points) < 2) {
            throw new InvalidArgumentException('At least two points are required to calculate a route.');
        }

        // Normalize points (ensure float)
        $pts = array_map(function ($p) {
            return ['lat' => (float)$p['lat'], 'lng' => (float)$p['lng']];
        }, $points);

        $totalMeters = 0.0;
        $steps = [];
        for ($i = 0, $n = count($pts) - 1; $i < $n; $i++) {
            $a = $pts[$i];
            $b = $pts[$i + 1];
            $dist = $this->haversineMeters($a['lat'], $a['lng'], $b['lat'], $b['lng']);
            $totalMeters += $dist;

            $speedKmh = $this->speeds[$mode] ?? $this->speeds['car'];
            // convert km/h to m/s
            $speedMs = ($speedKmh * 1000.0) / 3600.0;
            $duration = $speedMs > 0 ? (int) round($dist / $speedMs) : 0;

            $steps[] = [
                'instruction' => "Segment " . ($i + 1),
                'distance' => (int) round($dist),
                'duration' => $duration,
                'lat' => $b['lat'],
                'lng' => $b['lng']
            ];
        }

        $poly = $polyline ?? $this->encodePolyline($pts);
        $result = [
            'polyline' => $poly,
            'distance_meters' => (int) round($totalMeters),
            'duration_seconds' => (int) array_sum(array_column($steps, 'duration')),
            'steps' => $steps,
            'calculated_via' => 'fake',
        ];

        return $result;
    }

    /**
     * Haversine formula — returns meters (double).
     */
    public function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // all arithmetic done carefully
        $earthRadius = 6371000.0; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $rLat1 = deg2rad($lat1);
        $rLat2 = deg2rad($lat2);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos($rLat1) * cos($rLat2) * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Decode Google-encoded polyline into array of points.
     * Standard implementation — deterministic.
     *
     * @param string $polyline
     * @return array
     */
    public function decodePolyline(string $polyline): array
    {
        $len = strlen($polyline);
        $index = 0;
        $lat = 0;
        $lng = 0;
        $points = [];

        while ($index < $len) {
            $result = 1;
            $shift = 0;
            $b = 0;
            do {
                $b = ord($polyline[$index++]) - 63 - 1;
                $result += $b << $shift;
                $shift += 5;
            } while ($b >= 0x1f && $index < $len);
            $dlat = ($result & 1) ? ~($result >> 1) : ($result >> 1);
            $lat += $dlat;

            $result = 1;
            $shift = 0;
            do {
                $b = ord($polyline[$index++]) - 63 - 1;
                $result += $b << $shift;
                $shift += 5;
            } while ($b >= 0x1f && $index < $len);
            $dlng = ($result & 1) ? ~($result >> 1) : ($result >> 1);
            $lng += $dlng;

            $points[] = ['lat' => $lat * 1e-5, 'lng' => $lng * 1e-5];
        }

        return $points;
    }

    /**
     * Encode array of points into Google polyline.
     *
     * @param array $points
     * @return string
     */
    public function encodePolyline(array $points): string
    {
        $lastLat = 0;
        $lastLng = 0;
        $result = '';

        foreach ($points as $p) {
            $lat = (int) round($p['lat'] * 1e5);
            $lng = (int) round($p['lng'] * 1e5);

            $dlat = $lat - $lastLat;
            $dlng = $lng - $lastLng;

            $result .= $this->encodeSignedNumber($dlat);
            $result .= $this->encodeSignedNumber($dlng);

            $lastLat = $lat;
            $lastLng = $lng;
        }

        return $result;
    }

    protected function encodeSignedNumber($num)
    {
        $num = $num << 1;
        if ($num < 0) {
            $num = ~$num;
        }
        return $this->encodeNumber($num);
    }

    protected function encodeNumber($num)
    {
        $result = '';
        while ($num >= 0x20) {
            $result .= chr((0x20 | ($num & 0x1f)) + 63);
            $num >>= 5;
        }
        $result .= chr($num + 63);
        return $result;
    }
}

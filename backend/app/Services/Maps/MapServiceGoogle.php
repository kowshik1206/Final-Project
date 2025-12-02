<?php

namespace App\Services\Maps;

use App\Services\DTOs\RouteData;
use Illuminate\Support\Facades\Http;

class MapServiceGoogle implements MapServiceInterface
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google.maps_api_key') ?? env('GOOGLE_MAPS_API_KEY', '');
    }

    public function calculateRoute(
        float $originLat,
        float $originLng,
        float $destinationLat,
        float $destinationLng,
        ?string $preference = 'fastest'
    ): RouteData {
        if (!$this->apiKey) {
            throw new \Exception('Google Maps API key not configured');
        }

        $origin = "$originLat,$originLng";
        $destination = "$destinationLat,$destinationLng";

        $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
            'origin' => $origin,
            'destination' => $destination,
            'key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch route from Google Maps API');
        }

        $data = $response->json();
        
        if ($data['status'] !== 'OK' || empty($data['routes'])) {
            throw new \Exception('No route found');
        }

        $route = $data['routes'][0];
        $leg = $route['legs'][0];

        return new RouteData(
            distanceMeters: $leg['distance']['value'],
            durationSeconds: $leg['duration']['value'],
            polyline: $route['overview_polyline']['points'],
            points: [
                ['lat' => $originLat, 'lng' => $originLng],
                ['lat' => $destinationLat, 'lng' => $destinationLng],
            ],
            preference: $preference ?? 'fastest',
        );
    }

    public function getDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
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

    public function decodePolyline(string $polyline): array
    {
        $points = [];
        $index = 0;
        $lat = 0;
        $lng = 0;
        $change = 0;

        for ($i = 0; $i < strlen($polyline); $i++) {
            $byte = ord($polyline[$i]) - 63;
            $isLat = ($i % 2) == 0;

            $change = 0;
            for ($j = 0; $j < 5; $j++) {
                $bit = ($byte >> $j) & 1;
                $change |= ($bit << $j);
            }

            if (($change & 1) != 0) {
                $change = ~($change >> 1);
            } else {
                $change = $change >> 1;
            }

            if ($isLat) {
                $lat += $change;
                $points[] = ['lat' => $lat / 1e5, 'lng' => $lng / 1e5];
            } else {
                $lng += $change;
            }
        }

        return $points;
    }

    public function encodePolyline(array $coordinates): string
    {
        // Google polyline encoding algorithm
        $encoded = '';
        $prevLat = 0;
        $prevLng = 0;

        foreach ($coordinates as $point) {
            $lat = intval(round($point['lat'] * 1e5));
            $lng = intval(round($point['lng'] * 1e5));

            $encoded .= $this->encodeValue($lat - $prevLat);
            $encoded .= $this->encodeValue($lng - $prevLng);

            $prevLat = $lat;
            $prevLng = $lng;
        }

        return $encoded;
    }

    private function encodeValue(int $value): string
    {
        $value = $value << 1;
        if ($value < 0) {
            $value = ~$value;
        }

        $encoded = '';
        while ($value >= 0x20) {
            $encoded .= chr((0x20 | ($value & 0x1f)) + 63);
            $value >>= 5;
        }
        $encoded .= chr($value + 63);

        return $encoded;
    }
}

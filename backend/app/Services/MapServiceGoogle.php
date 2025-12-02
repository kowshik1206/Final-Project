<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Minimal Google Maps wrapper. In production set MAPS_PROVIDER=google and MAPS_API_KEY in .env.
 * This implementation is intentionally conservative: on any error it throws so factory can fallback.
 */
class MapServiceGoogle implements MapServiceInterface
{
    protected string $apiKey;
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->apiKey = config('services.maps.key', env('MAPS_API_KEY', ''));
        $this->logger = $logger;
    }

    public function calculateRoute(?array $points, ?string $polyline, string $preference = 'fastest', string $mode = 'car'): array
    {
        // If no API key present, throw to allow fallback
        if (empty($this->apiKey)) {
            throw new Exception('Google Maps API key not configured.');
        }

        // Build waypoints from points or polyline
        if ($polyline !== null) {
            // decode polyline to points (use fake entropy)
            $points = (new MapServiceFake())->decodePolyline($polyline);
        }

        if (empty($points) || count($points) < 2) {
            throw new Exception('At least two points required for Google call.');
        }

        // Build origin/destination and intermediate waypoints
        $origin = $points[0]['lat'] . ',' . $points[0]['lng'];
        $destination = end($points)['lat'] . ',' . end($points)['lng'];
        $via = [];
        if (count($points) > 2) {
            $mid = array_slice($points, 1, -1);
            foreach ($mid as $m) {
                $via[] = $m['lat'] . ',' . $m['lng'];
            }
        }

        $params = [
            'origin' => $origin,
            'destination' => $destination,
            'key' => $this->apiKey,
            'mode' => $this->modeToGoogle($mode),
        ];
        if (!empty($via)) {
            $params['waypoints'] = implode('|', $via);
        }

        $url = 'https://maps.googleapis.com/maps/api/directions/json';
        $resp = Http::get($url, $params);
        if (!$resp->ok()) {
            $this->logger->warning('Google Directions API returned non-200', ['status' => $resp->status()]);
            throw new Exception('Google Directions API error');
        }
        $json = $resp->json();
        if (!isset($json['routes'][0])) {
            throw new Exception('No routes from Google API');
        }

        // parse route: sum legs
        $route = $json['routes'][0];
        $legs = $route['legs'] ?? [];
        $distanceMeters = 0;
        $durationSeconds = 0;
        $steps = [];
        foreach ($legs as $leg) {
            $distanceMeters += $leg['distance']['value'] ?? 0;
            $durationSeconds += $leg['duration']['value'] ?? 0;
            foreach ($leg['steps'] ?? [] as $s) {
                $steps[] = [
                    'instruction' => strip_tags($s['html_instructions'] ?? ''),
                    'distance' => $s['distance']['value'] ?? 0,
                    'duration' => $s['duration']['value'] ?? 0,
                    'lat' => $s['end_location']['lat'] ?? null,
                    'lng' => $s['end_location']['lng'] ?? null
                ];
            }
        }

        return [
            'polyline' => $route['overview_polyline']['points'] ?? '',
            'distance_meters' => (int)$distanceMeters,
            'duration_seconds' => (int)$durationSeconds,
            'steps' => $steps,
            'calculated_via' => 'google',
        ];
    }

    public function decodePolyline(string $polyline): array
    {
        return (new MapServiceFake())->decodePolyline($polyline);
    }

    public function encodePolyline(array $points): string
    {
        return (new MapServiceFake())->encodePolyline($points);
    }

    protected function modeToGoogle(string $mode): string
    {
        return match ($mode) {
            'train' => 'transit',
            'flight' => 'driving', // no flight option in directions endpoint — fallback
            default => $mode,
        };
    }
}

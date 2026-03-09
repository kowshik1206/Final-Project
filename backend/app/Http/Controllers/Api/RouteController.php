<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class RouteController extends Controller
{
    /**
     * Plan a route between source and destination using OSRM
     * Expected request body: { source: "place name", destination: "place name" }
     */
    public function planRoute(Request $request)
    {
        Log::info('🛣️ Plan route endpoint called', ['body' => $request->all()]);

        $source = $request->input('source');
        $destination = $request->input('destination');

        if (!$source || !$destination) {
            Log::warning('❌ Missing source or destination');
            return response()->json(['ok' => false, 'message' => 'source and destination required'], 400);
        }

        try {
            // Step 1: Geocode source and destination using Nominatim
            Log::info('📍 Geocoding source', ['source' => $source]);
            $sourceCoords = $this->geocodePlace($source);
            if (!$sourceCoords) {
                Log::warning('❌ Could not geocode source', ['source' => $source]);
                return response()->json(['ok' => false, 'message' => 'Could not find source location'], 400);
            }
            Log::info('✅ Source geocoded', $sourceCoords);

            Log::info('📍 Geocoding destination', ['destination' => $destination]);
            $destCoords = $this->geocodePlace($destination);
            if (!$destCoords) {
                Log::warning('❌ Could not geocode destination', ['destination' => $destination]);
                return response()->json(['ok' => false, 'message' => 'Could not find destination location'], 400);
            }
            Log::info('✅ Destination geocoded', $destCoords);

            // Step 2: Call OSRM routing service
            // Format: [lon,lat];[lon,lat]
            $coordsStr = "{$sourceCoords['lon']},{$sourceCoords['lat']};{$destCoords['lon']},{$destCoords['lat']}";
            $osrmUrl = "http://router.project-osrm.org/route/v1/driving/{$coordsStr}?overview=full&geometries=geojson";
            
            Log::info('🛣️ Calling OSRM routing service', ['url' => substr($osrmUrl, 0, 150)]);

            $resp = Http::timeout(60)->get($osrmUrl);
            Log::info('📡 OSRM response received', ['status' => $resp->status()]);

            if ($resp->failed()) {
                Log::error('❌ OSRM request failed', ['status' => $resp->status(), 'body' => substr($resp->body(), 0, 200)]);
                return response()->json(['ok' => false, 'message' => 'Routing service error: ' . $resp->status()], 502);
            }

            $json = $resp->json();
            Log::debug('OSRM response keys', ['keys' => array_keys($json)]);

            // Check for errors in OSRM response
            if ($json['code'] !== 'Ok') {
                Log::error('❌ OSRM returned error code', ['code' => $json['code'], 'message' => $json['message'] ?? '']);
                return response()->json(['ok' => false, 'message' => 'No route found: ' . ($json['message'] ?? $json['code'])], 404);
            }

            // Extract route
            if (empty($json['routes'])) {
                Log::error('❌ OSRM returned no routes', ['response' => $json]);
                return response()->json(['ok' => false, 'message' => 'No route available'], 404);
            }

            $route = $json['routes'][0];
            $distance_m = $route['distance'] ?? 0;
            $duration_s = $route['duration'] ?? 0;
            $distance_km = round($distance_m / 1000, 2);
            $duration_min = round($duration_s / 60, 1);

            // Extract polyline (GeoJSON LineString geometry)
            $geometry = $route['geometry'] ?? null;
            $polyline = [];
            if ($geometry && is_array($geometry['coordinates'])) {
                // geometry.coordinates is [[lon,lat], [lon,lat], ...]
                // Convert to [{lat, lng}, {lat, lng}, ...]
                $polyline = array_map(function ($coord) {
                    return ['lat' => $coord[1], 'lng' => $coord[0]];
                }, $geometry['coordinates']);
            }

            Log::info('✅ Route computed successfully', [
                'distance_km' => $distance_km,
                'duration_min' => $duration_min,
                'polyline_points' => count($polyline)
            ]);

            return response()->json([
                'ok' => true,
                'distance_km' => $distance_km,
                'duration_min' => $duration_min,
                'distance_m' => $distance_m,
                'duration_s' => $duration_s,
                'polyline' => $polyline,
                'source' => $source,
                'destination' => $destination,
                'source_coords' => $sourceCoords,
                'dest_coords' => $destCoords
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Route planning exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json(['ok' => false, 'message' => 'Route planning error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Geocode a place name using Nominatim (OSM)
     */
    private function geocodePlace($place)
    {
        try {
            $q = urlencode($place);
            $url = "https://nominatim.openstreetmap.org/search?q={$q}&format=json&limit=1";
            $resp = Http::withHeaders(['Accept' => 'application/json'])->timeout(15)->get($url);
            
            if ($resp->failed() || $resp->json() === []) {
                return null;
            }
            
            $results = $resp->json();
            if (!empty($results[0])) {
                return [
                    'lat' => floatval($results[0]['lat']),
                    'lon' => floatval($results[0]['lon']),
                    'display_name' => $results[0]['display_name'] ?? $place
                ];
            }
            return null;
        } catch (\Exception $e) {
            Log::warning('Geocode exception', ['place' => $place, 'error' => $e->getMessage()]);
            return null;
        }
    }
}

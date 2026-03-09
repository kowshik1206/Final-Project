<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PoiController extends Controller
{
    // Map category keys to OSM tags
    private $poiTags = [
        'fuel' => ['amenity=fuel'],
        'ev_charger' => ['amenity=charging_station'],
        'charger' => ['amenity=charging_station'],
        'restaurant' => ['amenity=restaurant', 'amenity=cafe'],
        'hospital' => ['amenity=hospital', 'amenity=clinic'],
        'temple' => ['historic=temple', 'amenity=place_of_worship'],
        'toll' => ['highway=toll_booth'],
        'cng' => ['amenity=fuel']
    ];

    /**
     * Get all POIs
     */
    public function index()
    {
        $pois = Poi::all();
        return response()->json(['success' => true, 'data' => $pois]);
    }

    /**
     * Create a new POI
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'category' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $poi = Poi::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'POI created successfully',
            'data' => $poi,
        ], 201);
    }

    /**
     * Get nearby POIs
     */
    public function nearby(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|numeric',
        ]);

        $pois = Poi::whereBetween('latitude', [
            $validated['latitude'] - $validated['radius'],
            $validated['latitude'] + $validated['radius'],
        ])->whereBetween('longitude', [
            $validated['longitude'] - $validated['radius'],
            $validated['longitude'] + $validated['radius'],
        ])->get();

        return response()->json(['success' => true, 'data' => $pois]);
    }

    /**
     * Fetch POIs along a route from Overpass API
     * Expected request body: { routeCoords: [[lon,lat], ...], categories: ['fuel', 'restaurant', ...] }
     */
    public function fetchForRoute(Request $request)
    {
        $route = $request->input('routeCoords', []);
        $categories = $request->input('categories', array_keys($this->poiTags));

        // Validate route
        if (!is_array($route) || count($route) < 2) {
            return response()->json(['pois' => [], 'error' => 'invalid-route'], 400);
        }

        // Compute bbox from route coords (expected [[lon,lat], ...])
        $minLon = $maxLon = floatval($route[0][0] ?? 0);
        $minLat = $maxLat = floatval($route[0][1] ?? 0);
        
        foreach ($route as $pt) {
            if (!is_array($pt) || count($pt) < 2) continue;
            $lon = floatval($pt[0]);
            $lat = floatval($pt[1]);
            if ($lon < $minLon) $minLon = $lon;
            if ($lon > $maxLon) $maxLon = $lon;
            if ($lat < $minLat) $minLat = $lat;
            if ($lat > $maxLat) $maxLat = $lat;
        }

        // Add buffer (~0.05 deg ~ 5km; adjust for testing)
        $buffer = 0.1;
        $south = $minLat - $buffer;
        $west = $minLon - $buffer;
        $north = $maxLat + $buffer;
        $east = $maxLon + $buffer;

        // Build Overpass query
        $pieces = [];
        foreach ($categories as $cat) {
            $cat = strtolower(trim($cat));
            if (!isset($this->poiTags[$cat])) {
                continue;
            }
            foreach ($this->poiTags[$cat] as $tag) {
                // node, way, and relations
                $pieces[] = "node[{$tag}]({$south},{$west},{$north},{$east});";
                $pieces[] = "way[{$tag}]({$south},{$west},{$north},{$east});";
            }
        }

        if (empty($pieces)) {
            Log::warning('❌ No valid categories for Overpass', ['requested' => $categories]);
            return response()->json(['pois' => [], 'error' => 'no-categories']);
        }

        $query = "[out:json][timeout:30];(" . implode('', $pieces) . ");out center 300;";
        Log::info('🔎 Overpass query (first 300 chars)', ['query' => substr($query, 0, 300)]);

        try {
            Log::info('📡 Posting to Overpass API...');
            $resp = Http::withHeaders(['Content-Type' => 'text/plain; charset=UTF-8'])
                        ->timeout(35)
                        ->post('https://overpass-api.de/api/interpreter', $query);

            Log::info('📨 Overpass response received', [
                'status' => $resp->status(),
                'ok' => $resp->ok()
            ]);

            if ($resp->failed()) {
                Log::error('❌ Overpass request failed', [
                    'status' => $resp->status(),
                    'body' => substr($resp->body(), 0, 200)
                ]);
                return response()->json([
                    'pois' => [],
                    'error' => 'overpass-failed',
                    'status' => $resp->status()
                ], 502);
            }

            $json = $resp->json();
            $elements = $json['elements'] ?? [];

            Log::info('✅ Overpass returned elements', ['count' => count($elements)]);

            // Normalize POIs
            $pois = [];
            foreach ($elements as $el) {
                // Get lat/lon from element or center
                $lat = $el['lat'] ?? ($el['center']['lat'] ?? null);
                $lon = $el['lon'] ?? ($el['center']['lon'] ?? null);
                
                if (!$lat || !$lon) continue;

                // Try to determine category from tags
                $catFound = $this->determineCategoryFromTags($el['tags'] ?? []);

                $pois[] = [
                    'id' => $el['id'],
                    'osmType' => $el['type'],
                    'name' => $el['tags']['name'] ?? ($el['tags']['name:en'] ?? ($catFound ?? 'POI')),
                    'lat' => floatval($lat),
                    'lng' => floatval($lon),
                    'category' => $catFound,
                    'tags' => $el['tags'] ?? []
                ];
            }

            Log::info('✅ Normalized POIs count', ['count' => count($pois)]);

            return response()->json(['pois' => $pois, 'count' => count($pois)]);

        } catch (\Exception $e) {
            Log::error('❌ Overpass exception', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'pois' => [],
                'error' => 'exception',
                'msg' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determine category from OSM tags
     */
    private function determineCategoryFromTags($tags)
    {
        if (!is_array($tags)) return null;

        // Check each category's tag patterns
        foreach ($this->poiTags as $cat => $tagList) {
            foreach ($tagList as $tag) {
                [$key, $val] = explode('=', $tag, 2);
                if (isset($tags[$key])) {
                    $tagVal = $tags[$key];
                    // Match exact value or partial match
                    if ($val === $tagVal || strpos($tagVal, $val) !== false) {
                        return $cat;
                    }
                }
            }
        }
        return null;
    }
}

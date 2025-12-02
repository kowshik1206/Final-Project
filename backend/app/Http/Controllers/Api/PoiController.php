<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PoiController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Poi::query();

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by tags
            if ($request->has('tags')) {
                $tags = $request->input('tags');
                $query->where(function ($q) use ($tags) {
                    foreach ($tags as $tag) {
                        $q->orWhereJsonContains('tags', $tag);
                    }
                });
            }

            // Bounding box filter (lat_min, lat_max, lng_min, lng_max)
            if ($request->has('bbox')) {
                ['lat_min' => $latMin, 'lat_max' => $latMax, 'lng_min' => $lngMin, 'lng_max' => $lngMax]
                    = $request->input('bbox');

                $query->whereBetween('latitude', [$latMin, $latMax])
                    ->whereBetween('longitude', [$lngMin, $lngMax]);
            }

            $pois = $query->paginate($request->input('per_page', 20));

            return response()->json([
                'status' => 'success',
                'data' => $pois->items(),
                'pagination' => [
                    'total' => $pois->total(),
                    'per_page' => $pois->perPage(),
                    'current_page' => $pois->currentPage(),
                    'last_page' => $pois->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function show($id)
    {
        try {
            $poi = Poi::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $poi,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'POI not found',
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:temple,fuel,charger,toll,restaurant,hospital,other',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'address' => 'nullable|string',
                'tags' => 'nullable|array',
                'attributes' => 'nullable|array',
            ]);

            $poi = Poi::create([
                ...$validated,
                'created_by' => auth()->id(),
                'tags' => $validated['tags'] ?? [],
                'attributes' => $validated['attributes'] ?? [],
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $poi,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $poi = Poi::findOrFail($id);

            // Authorization check
            if ($poi->created_by !== auth()->id() && auth()->id() !== 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'type' => 'sometimes|required|in:temple,fuel,charger,toll,restaurant,hospital,other',
                'latitude' => 'sometimes|required|numeric|between:-90,90',
                'longitude' => 'sometimes|required|numeric|between:-180,180',
                'address' => 'nullable|string',
                'tags' => 'nullable|array',
                'attributes' => 'nullable|array',
            ]);

            $poi->update($validated);

            return response()->json([
                'status' => 'success',
                'data' => $poi,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function destroy($id)
    {
        try {
            $poi = Poi::findOrFail($id);

            // Authorization check
            if ($poi->created_by !== auth()->id() && auth()->id() !== 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 403);
            }

            $poi->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'POI deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function nearRoute(Request $request)
    {
        try {
            $validated = $request->validate([
                'polyline' => 'required|string',
                'radius_km' => 'required|numeric|min:0.1|max:50',
                'type' => 'nullable|in:temple,fuel,charger,toll,restaurant,hospital,other',
            ]);

            $polyline = $validated['polyline'];
            $radiusKm = $validated['radius_km'];
            $type = $validated['type'] ?? null;

            // Decode polyline to get points
            $points = $this->decodePolyline($polyline);

            if (empty($points)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid polyline',
                ], 400);
            }

            // Find POIs near any point on the route
            $query = Poi::query();

            if ($type) {
                $query->where('type', $type);
            }

            $nearPois = [];
            $seen = [];

            foreach ($points as $point) {
                $lat = $point['lat'];
                $lng = $point['lng'];

                // Use raw SQL for distance calculation with Haversine formula
                $pois = $query
                    ->selectRaw(
                        "*, 
                        (6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * 
                        cos(radians(longitude) - radians($lng)) + 
                        sin(radians($lat)) * sin(radians(latitude)))) AS distance_km"
                    )
                    ->havingRaw("distance_km <= $radiusKm")
                    ->orderBy('distance_km')
                    ->get();

                foreach ($pois as $poi) {
                    if (!isset($seen[$poi->id])) {
                        $seen[$poi->id] = true;
                        $nearPois[] = $poi;
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => array_slice($nearPois, 0, 50), // Limit to 50 results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    private function decodePolyline($polyline)
    {
        $points = [];
        $index = 0;
        $lat = 0;
        $lng = 0;

        while ($index < strlen($polyline)) {
            $result = 0;
            $shift = 0;

            do {
                $char = ord(substr($polyline, $index++, 1)) - 63;
                $result |= ($char & 0x1f) << $shift;
                $shift += 5;
            } while ($char >= 0x20);

            $dLat = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lat += $dLat;

            $result = 0;
            $shift = 0;

            do {
                $char = ord(substr($polyline, $index++, 1)) - 63;
                $result |= ($char & 0x1f) << $shift;
                $shift += 5;
            } while ($char >= 0x20);

            $dLng = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lng += $dLng;

            $points[] = [
                'lat' => $lat / 1e5,
                'lng' => $lng / 1e5,
            ];
        }

        return $points;
    }
}

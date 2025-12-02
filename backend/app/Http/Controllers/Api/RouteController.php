<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RouteCalcRequest;
use App\Services\MapServiceFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Route Controller
 *
 * Handles route calculation and geometry parsing via MapService abstraction.
 */
class RouteController extends Controller
{
    protected MapServiceFactory $factory;

    public function __construct(MapServiceFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * POST /api/route/calc
     *
     * Calculate route between points or from polyline.
     */
    public function calc(RouteCalcRequest $req): JsonResponse
    {
        $payload = $req->only(['points', 'polyline', 'mode', 'preference']);

        // Basic safety checks
        if (!empty($payload['points']) && count($payload['points']) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'At least two points are required',
                'errors' => [],
                'status' => 422
            ], 422);
        }

        // Normalize input for cache key
        $normalized = [
            'points' => $payload['points'] ?? null,
            'polyline' => $payload['polyline'] ?? null,
            'mode' => $payload['mode'] ?? 'car',
            'preference' => $payload['preference'] ?? 'fastest'
        ];

        $cacheKey = 'route:' . hash('sha256', json_encode($normalized));
        $cacheTTL = (int) env('ROUTE_CACHE_TTL', 86400);

        // Attempt cache read
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return response()->json(['status' => 'success', 'data' => $cached], 200)->header('X-Cache', 'HIT');
        }

        // Acquire service via factory; handle possible google failure with fallback
        $svc = $this->factory->make();
        try {
            $start = microtime(true);
            $res = $svc->calculateRoute($normalized['points'], $normalized['polyline'], $normalized['preference'], $normalized['mode']);
            $durationMs = (int) round((microtime(true) - $start) * 1000);
        } catch (\Throwable $e) {
            Log::warning('Route calc failed on provider: ' . $e->getMessage());
            // fallback to fake
            $svc = app(\App\Services\MapServiceFake::class);
            $start = microtime(true);
            $res = $svc->calculateRoute($normalized['points'], $normalized['polyline'], $normalized['preference'], $normalized['mode']);
            $res['calculated_via'] = 'fallback';
            $durationMs = (int) round((microtime(true) - $start) * 1000);
        }

        // Persist a compact api_log if table exists
        try {
            if (Schema::hasTable('api_logs')) {
                DB::table('api_logs')->insert([
                    'user_id' => auth()->id(),
                    'endpoint' => '/api/route/calc',
                    'payload' => json_encode(['input_hash' => $cacheKey]),
                    'response' => json_encode(['distance' => $res['distance_meters'], 'via' => $res['calculated_via']]),
                    'status_code' => 200,
                    'duration_ms' => $durationMs,
                    'created_at' => Carbon::now(),
                ]);
            }
        } catch (\Throwable $ex) {
            // swallow logging errors
            Log::debug('api_logs insert failed: ' . $ex->getMessage());
        }

        // Cache the result
        Cache::put($cacheKey, $res, $cacheTTL);

        return response()->json(['status' => 'success', 'data' => $res], 200)->header('X-Cache', 'MISS');
    }

    /**
     * POST /api/route/parse-geometry
     *
     * Decode polyline and return normalized points and re-encoded polyline.
     */
    public function parseGeometry(Request $req): JsonResponse
    {
        $polyline = $req->input('polyline');
        if (empty($polyline) || !is_string($polyline)) {
            return response()->json(['success' => false, 'message' => 'polyline required', 'errors' => [], 'status' => 422], 422);
        }

        $svc = $this->factory->make();
        $points = $svc->decodePolyline($polyline);
        $simplified = $svc->encodePolyline($points); // encode back to ensure normalized representation

        return response()->json([
            'status' => 'success',
            'data' => [
                'points' => $points,
                'polyline' => $simplified
            ]
        ], 200);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalcCostRequest;
use App\Services\CostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * CostController
 *
 * Handles cost calculation endpoints using CostService.
 * - POST /api/calc-cost — Calculate costs for given route and modes
 * - POST /api/calc-cost/preview — Calculate with sensitivity analysis
 */
class CostController extends Controller
{
    private CostService $costService;

    public function __construct(CostService $costService)
    {
        $this->costService = $costService;
    }

    /**
     * POST /api/calc-cost
     *
     * Calculate trip costs by mode(s) and distance.
     * Returns array of cost objects (one per requested mode).
     */
    public function calculateCost(CalcCostRequest $request): JsonResponse
    {
        try {
            $input = [
                'distance_meters' => $request->input('route.distance_meters'),
                'mode_preferences' => $request->input('mode_preferences', ['car']),
                'vehicle' => $request->input('vehicle', []),
            ];

            $result = $this->costService->calculate($input);
            $cached = $result['cached'] ?? false;
            $costs = $result['results'] ?? $result;

            $response = response()->json([
                'status' => 'success',
                'data' => [
                    'costs' => $costs,
                    'distance_km' => $input['distance_meters'] / 1000,
                ],
            ], 200);

            // Add cache header like Route Engine
            $response->header('X-Cache', $cached ? 'HIT' : 'MISS');

            return $response;
        } catch (\Throwable $e) {
            Log::error('Cost calculation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Cost calculation failed',
                'errors' => [],
                'status' => 500,
            ], 500);
        }
    }

    /**
     * POST /api/calc-cost/preview
     *
     * Calculate trip costs with sensitivity analysis.
     * Includes fuel price and efficiency effects for car mode.
     */
    public function previewCost(CalcCostRequest $request): JsonResponse
    {
        try {
            $input = [
                'distance_meters' => $request->input('route.distance_meters'),
                'mode_preferences' => $request->input('mode_preferences', ['car']),
                'vehicle' => $request->input('vehicle', []),
            ];

            $preview = $this->costService->calculatePreview($input);
            $cached = $preview['cached'] ?? false;

            $response = response()->json([
                'status' => 'success',
                'data' => [
                    'costs' => $preview['costs'],
                    'sensitivity' => $preview['sensitivity'],
                    'distance_km' => $input['distance_meters'] / 1000,
                ],
            ], 200);

            // Add cache header
            $response->header('X-Cache', $cached ? 'HIT' : 'MISS');

            return $response;
        } catch (\Throwable $e) {
            Log::error('Cost preview calculation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Cost preview calculation failed',
                'errors' => [],
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Deprecated: kept for backward compatibility
     * Use calculateCost instead
     */
    public function recommend(CalcCostRequest $request): JsonResponse
    {
        return $this->calculateCost($request);
    }
}

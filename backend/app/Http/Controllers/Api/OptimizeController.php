<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OptimizeService;
use Illuminate\Http\Request;

class OptimizeController extends Controller
{
    protected $optimizeService;

    public function __construct(OptimizeService $optimizeService)
    {
        $this->optimizeService = $optimizeService;
    }

    /**
     * Optimize route
     */
    public function optimize(Request $request)
    {
        $validated = $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'pois' => 'required|array',
            'mode' => 'required|in:car,bike,walk,transit',
        ]);

        $optimizedRoute = $this->optimizeService->optimizeRoute($validated);

        return response()->json([
            'success' => true,
            'data' => $optimizedRoute,
        ]);
    }

    /**
     * Get route suggestions
     */
    public function suggest(Request $request)
    {
        $validated = $request->validate([
            'start' => 'required|string',
            'end' => 'required|string',
            'mode' => 'required|in:car,bike,walk,transit',
        ]);

        $suggestions = $this->optimizeService->getRouteSuggestions($validated);

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CostService;
use Illuminate\Http\Request;

class CostController extends Controller
{
    protected $costService;

    public function __construct(CostService $costService)
    {
        $this->costService = $costService;
    }

    /**
     * Calculate cost for a trip
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'mode' => 'required|in:car,bike,walk,transit',
            'distance' => 'required|numeric',
        ]);

        $cost = $this->costService->calculateCost($validated);

        return response()->json([
            'success' => true,
            'data' => $cost,
        ]);
    }

    /**
     * Get cost breakdown
     */
    public function breakdown(Request $request)
    {
        $validated = $request->validate([
            'trip_id' => 'required|exists:trips,id',
        ]);

        $breakdown = $this->costService->getBreakdown($validated['trip_id']);

        return response()->json([
            'success' => true,
            'data' => $breakdown,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OptimizeMultiStopRequest;
use App\Services\Maps\MapServiceFake;
use App\Services\TSPService;

class OptimizeController extends Controller
{
    public function multiStop(OptimizeMultiStopRequest $request)
    {
        try {
            $mapService = new MapServiceFake();
            $tspService = new TSPService($mapService);

            $result = $tspService->optimize(
                origin: $request->origin,
                destination: $request->destination,
                waypoints: $request->waypoints,
                optimizeFor: $request->optimize_for ?? 'distance'
            );

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecommendRequest;
use App\Services\RecommendService;
use App\Services\CostService;
use App\Services\CostConfigService;
use App\Http\Traits\HandlesApiErrors;

class RecommendController extends Controller
{
    use HandlesApiErrors;

    protected RecommendService $recommendService;

    public function __construct(RecommendService $recommendService)
    {
        $this->recommendService = $recommendService;
    }

    /**
     * POST /api/recommend
     */
    public function recommend(RecommendRequest $req)
    {
        $payload = $req->validated();

        try {
            $res = $this->recommendService->recommend($payload);
            return response()->json(['status' => 'success', 'data' => $res], 200);
        } catch (\InvalidArgumentException $e) {
            return $this->apiError($e->getMessage(), [], 422);
        } catch (\Throwable $e) {
            // unexpected
            return $this->apiError('Internal error', [], 500);
        }
    }
}

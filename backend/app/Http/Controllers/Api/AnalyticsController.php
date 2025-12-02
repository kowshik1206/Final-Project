<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function summary()
    {
        try {
            $userId = Auth::id();

            // Get totals
            $trips = Trip::where('user_id', $userId);
            $totalTrips = $trips->count();
            $totalDistanceKm = $trips->sum(DB::raw('distance_meters')) / 1000;
            $totalCost = 0;

            // Calculate total cost from all trips (sum from JSON cost field)
            $tripCosts = Trip::where('user_id', $userId)->pluck('cost');
            foreach ($tripCosts as $cost) {
                if (is_array($cost)) {
                    foreach ($cost as $modeCost) {
                        if (is_array($modeCost) && isset($modeCost['total_cost'])) {
                            $totalCost += $modeCost['total_cost'];
                        }
                    }
                }
            }

            $avgCostPerKm = $totalDistanceKm > 0 ? $totalCost / $totalDistanceKm : 0;

            // Most used mode
            $mostUsedMode = Trip::where('user_id', $userId)
                ->select('mode', DB::raw('count(*) as count'))
                ->groupBy('mode')
                ->orderByDesc('count')
                ->first();

            // Mode usage breakdown
            $modeUsage = Trip::where('user_id', $userId)
                ->select('mode', DB::raw('count(*) as count'))
                ->groupBy('mode')
                ->get()
                ->map(fn($item) => ['mode' => $item->mode, 'count' => $item->count])
                ->toArray();

            // Monthly stats (last 12 months)
            $monthlyStats = Trip::where('user_id', $userId)
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month')
                ->selectRaw('COUNT(*) as trips')
                ->selectRaw('SUM(distance_meters) / 1000 as distance_km')
                ->selectRaw('0 as cost')
                ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
                ->orderBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'), 'desc')
                ->limit(12)
                ->get()
                ->toArray();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_trips' => $totalTrips,
                    'total_distance_km' => round($totalDistanceKm, 2),
                    'total_cost' => round($totalCost, 2),
                    'most_used_mode' => $mostUsedMode?->mode ?? null,
                    'avg_cost_per_km' => round($avgCostPerKm, 2),
                    'mode_usage' => $modeUsage,
                    'monthly_stats' => $monthlyStats,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

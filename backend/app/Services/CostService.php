<?php

namespace App\Services;

use App\Models\TripModeCost;

class CostService
{
    /**
     * Calculate cost for a trip
     */
    public function calculateCost($data)
    {
        $tripId = $data['trip_id'];
        $mode = $data['mode'];
        $distance = $data['distance'];

        $baseCost = $this->getBaseCost($mode);
        $distanceCost = $distance * $this->getDistanceRate($mode);
        $timeCost = $this->getTimeCost($mode);
        $totalCost = $baseCost + $distanceCost + $timeCost;

        $tripModeCost = TripModeCost::create([
            'trip_id' => $tripId,
            'mode' => $mode,
            'base_cost' => $baseCost,
            'distance_cost' => $distanceCost,
            'time_cost' => $timeCost,
            'total_cost' => $totalCost,
        ]);

        return $tripModeCost;
    }

    /**
     * Get cost breakdown for a trip
     */
    public function getBreakdown($tripId)
    {
        return TripModeCost::where('trip_id', $tripId)->get();
    }

    /**
     * Get base cost by mode
     */
    private function getBaseCost($mode)
    {
        $costs = [
            'car' => 5.00,
            'bike' => 2.00,
            'walk' => 0.00,
            'transit' => 2.50,
        ];

        return $costs[$mode] ?? 0;
    }

    /**
     * Get distance rate by mode (per km)
     */
    private function getDistanceRate($mode)
    {
        $rates = [
            'car' => 0.50,
            'bike' => 0.25,
            'walk' => 0.00,
            'transit' => 0.30,
        ];

        return $rates[$mode] ?? 0;
    }

    /**
     * Get time cost by mode
     */
    private function getTimeCost($mode)
    {
        $costs = [
            'car' => 1.00,
            'bike' => 0.50,
            'walk' => 0.00,
            'transit' => 0.75,
        ];

        return $costs[$mode] ?? 0;
    }
}

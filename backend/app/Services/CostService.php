<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * CostService
 *
 * Provides deterministic cost calculations for different transportation modes.
 * All costs rounded to exactly 2 decimal places.
 * 
 * Features:
 * - Caches results using normalized input hash (key: cost:{sha256(input)})
 * - Reads runtime defaults from CostConfigService (DB or env fallback)
 * - Returns 'cached' flag for cache HIT/MISS tracking
 *
 * FORMULAS (EXACT):
 * CAR: fuel_cost + driver_cost + toll_cost
 * EV: energy_cost * 1.1 (loss factor)
 * TRAIN: base_fare + (distance_km * fare_per_km); 10% discount on per-km if >200km
 * FLIGHT: airport_fee + (distance_km * cost_per_km) + fuel_surcharge
 */
class CostService
{
    protected CostConfigService $config;
    protected int $cacheTtl;

    public function __construct(CostConfigService $config)
    {
        $this->config = $config;
        $this->cacheTtl = (int) env('COST_CACHE_TTL', 3600);
    }

    /**
     * Calculate costs for given input (route + modes)
     *
     * @param array $input {
     *     'distance_meters': int,
     *     'mode_preferences': ['car', 'ev', 'train', 'flight'],
     *     'vehicle': optional array with vehicle-specific params
     * }
     * @return array Array of costs per mode, keyed by mode name, with 'cached' flag
     */
    public function calculate(array $input): array
    {
        $distanceMeters = $input['distance_meters'] ?? 0;
        $distanceKm = $distanceMeters / 1000;
        $modePreferences = $input['mode_preferences'] ?? ['car'];
        $vehicle = $input['vehicle'] ?? [];

        // Normalize and create cache key
        $normalized = [
            'distance_meters' => $distanceMeters,
            'mode_preferences' => $modePreferences,
            'vehicle' => $vehicle,
        ];
        $cacheKey = 'cost:' . hash('sha256', json_encode($normalized));

        // Try cache
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            $cached['cached'] = true;
            return $cached;
        }

        $results = [];

        foreach ($modePreferences as $mode) {
            $results[$mode] = $this->calculateForMode($mode, $distanceKm, $vehicle);
        }

        $output = [
            'results' => $results,
            'cached' => false
        ];

        // Persist in cache for TTL
        Cache::put($cacheKey, $output, $this->cacheTtl);

        return $output;
    }

    /**
     * Calculate cost for single mode
     *
     * @param string $mode (car|ev|train|flight)
     * @param float $distanceKm
     * @param array $vehicle Optional vehicle-specific parameters
     * @return array Cost breakdown with mode, total_cost, breakdown, assumptions, explanation
     */
    private function calculateForMode(string $mode, float $distanceKm, array $vehicle = []): array
    {
        return match ($mode) {
            'car' => $this->calculateCar($distanceKm, $vehicle),
            'ev' => $this->calculateEv($distanceKm, $vehicle),
            'train' => $this->calculateTrain($distanceKm, $vehicle),
            'flight' => $this->calculateFlight($distanceKm, $vehicle),
            default => [
                'mode' => $mode,
                'total_cost' => 0,
                'breakdown' => [],
                'assumptions' => [],
                'explanation' => 'Unknown mode'
            ]
        };
    }

    /**
     * Calculate CAR cost (petrol/diesel)
     *
     * Formula:
     * fuel_needed_l = distance_km / fuel_efficiency_km_per_l
     * fuel_cost = fuel_needed_l * fuel_price_per_l
     * driver_cost = distance_km * driver_rate_per_km
     * toll_cost = fuel_cost * toll_multiplier (default 5%)
     * total = fuel_cost + driver_cost + toll_cost
     */
    private function calculateCar(float $distanceKm, array $vehicle = []): array
    {
        $fuelEfficiency = $vehicle['fuel_efficiency_km_per_l']
            ?? (float) $this->config->get('default_fuel_efficiency_km_per_l', 15);
        $fuelPrice = $vehicle['fuel_price_per_l']
            ?? (float) $this->config->get('default_price_per_l', 95);
        $driverRate = $vehicle['driver_cost_per_km']
            ?? (float) $this->config->get('driver_rate_per_km', 10);
        $tollMultiplier = $vehicle['toll_multiplier']
            ?? ((float) $this->config->get('toll_rate_percent', 5) / 100);

        $fuelNeeded = $distanceKm / $fuelEfficiency;
        $fuelCost = round($fuelNeeded * $fuelPrice, 2);
        $driverCost = round($distanceKm * $driverRate, 2);
        $tollCost = round($fuelCost * $tollMultiplier, 2);

        $totalCost = round($fuelCost + $driverCost + $tollCost, 2);

        return [
            'mode' => 'car',
            'total_cost' => $totalCost,
            'breakdown' => [
                'fuel_cost' => $fuelCost,
                'driver_cost' => $driverCost,
                'toll_cost' => $tollCost,
                'energy_cost' => null,
                'base_fare' => null,
                'tax' => null,
            ],
            'assumptions' => [
                'distance_km' => round($distanceKm, 2),
                'fuel_efficiency_km_per_l' => $fuelEfficiency,
                'fuel_price_per_l' => $fuelPrice,
                'driver_rate_per_km' => $driverRate,
                'toll_multiplier' => $tollMultiplier,
                'ev_kwh_per_km' => null,
                'price_per_kwh' => null,
            ],
            'explanation' => "Car: ₹{$fuelCost} (fuel) + ₹{$driverCost} (driver) + ₹{$tollCost} (toll) = ₹{$totalCost}",
        ];
    }

    /**
     * Calculate EV cost
     *
     * Formula:
     * energy_kwh = distance_km * ev_kwh_per_km
     * energy_cost = energy_kwh * price_per_kwh
     * loss_factor = 1.1 (charging inefficiency)
     * total = energy_cost * loss_factor
     */
    private function calculateEv(float $distanceKm, array $vehicle = []): array
    {
        $evKwhPerKm = $vehicle['ev_kwh_per_km']
            ?? (float) $this->config->get('ev_kwh_per_km', 0.15);
        $pricePerKwh = $vehicle['price_per_kwh']
            ?? (float) $this->config->get('price_per_kwh', 9);
        $lossFactor = 1.1;

        $energyKwh = $distanceKm * $evKwhPerKm;
        $energyCost = round($energyKwh * $pricePerKwh, 2);
        $totalCost = round($energyCost * $lossFactor, 2);

        return [
            'mode' => 'ev',
            'total_cost' => $totalCost,
            'breakdown' => [
                'fuel_cost' => null,
                'driver_cost' => null,
                'toll_cost' => null,
                'energy_cost' => $energyCost,
                'base_fare' => null,
                'tax' => null,
            ],
            'assumptions' => [
                'distance_km' => round($distanceKm, 2),
                'fuel_efficiency_km_per_l' => null,
                'fuel_price_per_l' => null,
                'driver_rate_per_km' => null,
                'toll_multiplier' => null,
                'ev_kwh_per_km' => $evKwhPerKm,
                'price_per_kwh' => $pricePerKwh,
            ],
            'explanation' => "EV: ₹{$energyCost} (energy) × 1.1 (loss factor) = ₹{$totalCost}",
        ];
    }

    /**
     * Calculate TRAIN cost
     *
     * Formula:
     * per_km_fare = distance_km * fare_per_km
     * If distance > 200 km → per_km_fare *= 0.9 (10% discount on per-km portion)
     * total = base_fare + per_km_fare
     */
    private function calculateTrain(float $distanceKm, array $vehicle = []): array
    {
        $baseFare = $vehicle['base_fare']
            ?? (float) $this->config->get('train_base_fare', 50);
        $farePerKm = $vehicle['fare_per_km']
            ?? (float) $this->config->get('train_fare_per_km', 1);

        $perKmCost = round($distanceKm * $farePerKm, 2);

        // Apply 10% discount on per-km portion if distance > 200 km
        $discountApplied = false;
        if ($distanceKm > 200) {
            $perKmCost = round($perKmCost * 0.9, 2);
            $discountApplied = true;
        }

        $totalCost = round($baseFare + $perKmCost, 2);

        $explanation = $discountApplied
            ? "Train (>200km, 10% discount): ₹{$baseFare} (base) + ₹{$perKmCost} (per-km after discount) = ₹{$totalCost}"
            : "Train: ₹{$baseFare} (base) + ₹{$perKmCost} (per-km) = ₹{$totalCost}";

        return [
            'mode' => 'train',
            'total_cost' => $totalCost,
            'breakdown' => [
                'fuel_cost' => null,
                'driver_cost' => null,
                'toll_cost' => null,
                'energy_cost' => null,
                'base_fare' => round($baseFare, 2),
                'tax' => null,
            ],
            'assumptions' => [
                'distance_km' => round($distanceKm, 2),
                'fuel_efficiency_km_per_l' => null,
                'fuel_price_per_l' => null,
                'driver_rate_per_km' => null,
                'toll_multiplier' => null,
                'ev_kwh_per_km' => null,
                'price_per_kwh' => null,
            ],
            'explanation' => $explanation,
        ];
    }

    /**
     * Calculate FLIGHT cost
     *
     * Formula:
     * subtotal = airport_fee + (distance_km * cost_per_km)
     * fuel_surcharge = subtotal * (fuel_surcharge_percentage / 100)
     * total = subtotal + fuel_surcharge
     * No discount logic.
     */
    private function calculateFlight(float $distanceKm, array $vehicle = []): array
    {
        $airportFee = $vehicle['airport_fee']
            ?? (float) $this->config->get('flight_airport_fee', 500);
        $costPerKm = $vehicle['cost_per_km']
            ?? (float) $this->config->get('flight_cost_per_km', 1.5);
        $fuelSurchargePercent = $vehicle['fuel_surcharge_percentage']
            ?? ((float) $this->config->get('flight_fuel_surcharge', 0.05) * 100);

        $distanceCost = round($distanceKm * $costPerKm, 2);
        $subtotal = round($airportFee + $distanceCost, 2);
        $fuelSurcharge = round($subtotal * ($fuelSurchargePercent / 100), 2);
        $totalCost = round($subtotal + $fuelSurcharge, 2);

        return [
            'mode' => 'flight',
            'total_cost' => $totalCost,
            'breakdown' => [
                'fuel_cost' => null,
                'driver_cost' => null,
                'toll_cost' => null,
                'energy_cost' => null,
                'base_fare' => round($airportFee, 2),
                'tax' => round($fuelSurcharge, 2),
            ],
            'assumptions' => [
                'distance_km' => round($distanceKm, 2),
                'fuel_efficiency_km_per_l' => null,
                'fuel_price_per_l' => null,
                'driver_rate_per_km' => null,
                'toll_multiplier' => null,
                'ev_kwh_per_km' => null,
                'price_per_kwh' => null,
            ],
            'explanation' => "Flight: ₹{$airportFee} (airport) + ₹{$distanceCost} (distance) + ₹{$fuelSurcharge} (fuel surcharge) = ₹{$totalCost}",
        ];
    }

    /**
     * Calculate preview with sensitivity analysis
     *
     * @param array $input
     * @return array Cost data + sensitivity analysis
     */
    public function calculatePreview(array $input): array
    {
        $baseResults = $this->calculate($input);

        // Perform sensitivity analysis for car mode (example)
        $sensitivity = [];
        if (isset($baseResults['results']['car'])) {
            $distanceKm = ($input['distance_meters'] ?? 0) / 1000;
            $vehicle = $input['vehicle'] ?? [];

            // Fuel price ±5% effect
            $baseFuelPrice = (float) $this->config->get('default_price_per_l', 95);
            $fuelPlusDelta = $this->calculateCar(
                $distanceKm,
                array_merge($vehicle, ['fuel_price_per_l' => $baseFuelPrice * 1.05])
            );
            $fuelMinusDelta = $this->calculateCar(
                $distanceKm,
                array_merge($vehicle, ['fuel_price_per_l' => $baseFuelPrice * 0.95])
            );
            $sensitivity['fuel_price_effect'] = [
                'plus_5_percent' => round($fuelPlusDelta['total_cost'] - $baseResults['results']['car']['total_cost'], 2),
                'minus_5_percent' => round($baseResults['results']['car']['total_cost'] - $fuelMinusDelta['total_cost'], 2),
            ];

            // Efficiency ±10% effect
            $baseEff = (float) $this->config->get('default_fuel_efficiency_km_per_l', 15);
            $effPlus = $this->calculateCar(
                $distanceKm,
                array_merge($vehicle, ['fuel_efficiency_km_per_l' => $baseEff * 1.1])
            );
            $effMinus = $this->calculateCar(
                $distanceKm,
                array_merge($vehicle, ['fuel_efficiency_km_per_l' => $baseEff * 0.9])
            );
            $sensitivity['efficiency_effect'] = [
                'plus_10_percent' => round($baseResults['results']['car']['total_cost'] - $effPlus['total_cost'], 2),
                'minus_10_percent' => round($effMinus['total_cost'] - $baseResults['results']['car']['total_cost'], 2),
            ];
        }

        return [
            'costs' => $baseResults['results'],
            'sensitivity' => $sensitivity,
            'cached' => $baseResults['cached'],
        ];
    }
}

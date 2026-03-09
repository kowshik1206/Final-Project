<?php
// backend/api/ev_calculate.php
// EV Calculator API - Authoritative computation endpoint
// Domain: Electric vehicle trip planning (no vehicle profiles, pure math)
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?: [];

// Extract and validate inputs
$distanceKm = isset($input['distance_km']) ? floatval($input['distance_km']) : 0;
$batteryKwh = isset($input['battery_kwh']) ? floatval($input['battery_kwh']) : 0;
$whPerKm = isset($input['consumption_wh_per_km']) ? floatval($input['consumption_wh_per_km']) : 0;
$costPerKwh = isset($input['cost_per_kwh']) ? floatval($input['cost_per_kwh']) : 0;
$currentCharge = isset($input['current_charge_percent']) ? floatval($input['current_charge_percent']) : 100;

// Validation
if ($distanceKm <= 0) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid distance_km: must be > 0'
    ]);
    exit;
}

if ($batteryKwh <= 0) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid battery_kwh: must be > 0'
    ]);
    exit;
}

if ($whPerKm <= 0) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid consumption_wh_per_km: must be > 0'
    ]);
    exit;
}

if ($costPerKwh <= 0) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid cost_per_kwh: must be > 0'
    ]);
    exit;
}

if ($currentCharge < 0 || $currentCharge > 100) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid current_charge_percent: must be 0-100'
    ]);
    exit;
}

// ========== EV CALCULATIONS ==========

// 1. Energy consumed for the trip (kWh)
$energyConsumedKwh = ($distanceKm * $whPerKm) / 1000;

// 2. Total cost of trip (₹)
$totalCost = $energyConsumedKwh * $costPerKwh;

// 3. Current usable battery (kWh)
$usableBatteryKwh = ($batteryKwh * $currentCharge) / 100;

// 4. Range on current charge (km)
$rangeOnCurrentCharge = ($usableBatteryKwh * 1000) / $whPerKm;

// 5. Charging stops needed (integer, minimum 0)
$chargingStops = max(0, ceil($distanceKm / $rangeOnCurrentCharge) - 1);

// Return normalized result
http_response_code(200);
echo json_encode([
    'ok' => true,
    'distance_km' => round($distanceKm, 2),
    'battery_kwh' => round($batteryKwh, 2),
    'consumption_wh_per_km' => round($whPerKm, 2),
    'cost_per_kwh' => round($costPerKwh, 2),
    'current_charge_percent' => round($currentCharge, 1),
    'energy_consumed_kwh' => round($energyConsumedKwh, 2),
    'estimated_cost' => round($totalCost),
    'estimated_range_km' => round($rangeOnCurrentCharge, 1),
    'charging_stops' => (int)$chargingStops,
    'timestamp' => date('Y-m-d H:i:s')
]);

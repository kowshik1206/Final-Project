<?php
// backend/api/bus_calculate.php
// PHASE 3: Bus journey with standardized contract
// Returns unified journey response with segments array

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/journey_contract.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];

// Extract distance
$distanceKm = isset($input['distance_km']) ? floatval($input['distance_km']) : 0;

// Validation
if ($distanceKm <= 0) {
    rejectInvalidContract('Invalid distance_km: must be > 0', 'INVALID_INPUT', 400);
}

// ========== BUS CALCULATION CONSTANTS ==========
$avgSpeedKmph = 50;           // Average bus speed
$costPerKm = 2.0;              // Cost per km
$stopIntervalKm = 200;         // Stop interval
$stopDurationMin = 15;         // Time per stop

// ========== CALCULATIONS ==========

// Travel time (minutes)
$travelTimeMin = ($distanceKm / $avgSpeedKmph) * 60;

// Number of stops (integer)
$stops = max(ceil($distanceKm / $stopIntervalKm) - 1, 0);

// Stop time (minutes)
$stopTimeMin = $stops * $stopDurationMin;

// Total duration (minutes)
$totalDurationMin = $travelTimeMin + $stopTimeMin;

// Cost (₹)
$estimatedCost = $distanceKm * $costPerKm;

// ========== PHASE 3: BUILD SEGMENTS ARRAY ==========
// Bus uses road-only: [road]
$segments = [
    [
        'type' => 'road',
        'label' => 'Road Journey',
        'from' => 'source',
        'to' => 'destination',
        'from_name' => 'Source',
        'to_name' => 'Destination',
        'distance_km' => round($distanceKm, 2),
        'duration_min' => intval($totalDurationMin),
        'polyline' => []  // Empty for calculation endpoints (frontend will request map data separately if needed)
    ]
];

// ========== RETURN UNIFIED CONTRACT ==========
$response = buildJourneyResponse(
    'bus',
    $distanceKm,
    intval($totalDurationMin),
    $estimatedCost,
    $input['source'] ?? 'Unknown',
    $input['destination'] ?? 'Unknown',
    $input['source_coords'] ?? ['lat' => 0, 'lng' => 0],
    $input['destination_coords'] ?? ['lat' => 0, 'lng' => 0],
    $segments
);

http_response_code(200);
echo json_encode($response);


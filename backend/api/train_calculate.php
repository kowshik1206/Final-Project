<?php
// backend/api/train_calculate.php
// PHASE 3: Train journey with standardized contract
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

// ========== TRAIN CONSTANTS (v2.2) ==========
$avgSpeedKmph = 75;           // Realistic speed including stops (Rajdhani avg ~75-80)
$boardingBufferMin = 45;      // Platform time + boarding
$costPerKm = 1.2;             // ₹1.2 per km
$minCost = 150;               // Minimum fare (short trips)

// ========== CALCULATIONS ==========
$travelTimeMin = ($distanceKm / $avgSpeedKmph) * 60;
$totalDurationMin = $travelTimeMin + $boardingBufferMin;
$estimatedCost = max($distanceKm * $costPerKm, $minCost);

// ========== PHASE 3: BUILD SEGMENTS ARRAY ==========
// Train uses: [road, rail, road]
// For the calculate endpoint (without actual station data), we estimate:
// - Road segments: 10 km each (to/from station)
// - Rail segment: remaining distance
$roadSegmentDistance = 10;  // Approximate
$railSegmentDistance = max($distanceKm - (2 * $roadSegmentDistance), $distanceKm);

// Recalculate if needed to match total
if ($railSegmentDistance + (2 * $roadSegmentDistance) !== $distanceKm) {
    $railSegmentDistance = $distanceKm - (2 * $roadSegmentDistance);
}

$segments = [
    [
        'type' => 'road',
        'label' => 'Road to Station',
        'from' => 'source',
        'to' => 'source_station',
        'from_name' => 'Source',
        'to_name' => 'Station',
        'distance_km' => round($roadSegmentDistance, 2),
        'duration_min' => intval(($roadSegmentDistance / 60) * 60),  // ~10 min for 10 km
        'polyline' => []
    ],
    [
        'type' => 'rail',
        'label' => 'Train Journey',
        'from' => 'source_station',
        'to' => 'dest_station',
        'from_name' => 'Station',
        'to_name' => 'Station',
        'distance_km' => round($railSegmentDistance, 2),
        'duration_min' => intval($travelTimeMin),
        'polyline' => []
    ],
    [
        'type' => 'road',
        'label' => 'Road from Station',
        'from' => 'dest_station',
        'to' => 'destination',
        'from_name' => 'Station',
        'to_name' => 'Destination',
        'distance_km' => round($roadSegmentDistance, 2),
        'duration_min' => intval(($roadSegmentDistance / 60) * 60),  // ~10 min for 10 km
        'polyline' => []
    ]
];

// ========== RETURN UNIFIED CONTRACT ==========
$response = buildJourneyResponse(
    'train',
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

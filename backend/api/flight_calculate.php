<?php
// backend/api/flight_calculate.php
// Returns unified journey response for Flight mode

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/journey_contract.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];

// Extract distance
$distanceKm = isset($input['distance_km']) ? floatval($input['distance_km']) : 0;

// Validation
if ($distanceKm <= 0) {
    rejectInvalidContract('Invalid distance_km: must be > 0', 'INVALID_INPUT', 400);
}

// ========== FLIGHT CONSTANTS ==========
$avgSpeedKmph = 800;          // Cruising speed
$boardingBufferMin = 120;     // 2 hours for check-in/security
$costPerKm = 5.0;             // ₹5 per km
$minCost = 3000;              // Minimum fare

// ========== CALCULATIONS ==========
$travelTimeMin = ($distanceKm / $avgSpeedKmph) * 60;
$roadDurationMin = (25 / 40) * 60 * 2; // Two 25km segments at 40km/h
$totalDurationMin = $travelTimeMin + $boardingBufferMin + $roadDurationMin;
$estimatedCost = max($distanceKm * $costPerKm, $minCost);

// ========== SEGMENTS ==========
// Flight uses: [road, flight, road]
// Estimate:
// - Road segments: 25 km each (airport usually far)
// - Flight segment: remaining distance
$roadSegmentDistance = 25;  
$flightSegmentDistance = max($distanceKm - (2 * $roadSegmentDistance), $distanceKm);

// Recalculate if needed
if ($flightSegmentDistance + (2 * $roadSegmentDistance) !== $distanceKm) {
    $flightSegmentDistance = $distanceKm - (2 * $roadSegmentDistance);
}

$segments = [
    [
        'type' => 'road',
        'label' => 'Road to Airport',
        'from' => 'source',
        'to' => 'source_airport',
        'from_name' => 'Source',
        'to_name' => 'Airport',
        'distance_km' => round($roadSegmentDistance, 2),
        'duration_min' => intval(($roadSegmentDistance / 40) * 60),  // ~40 km/h in city traffic
        'polyline' => []
    ],
    [
        'type' => 'flight',
        'label' => 'Flight Journey (incl. 2h check-in)',
        'from' => 'source_airport',
        'to' => 'dest_airport',
        'from_name' => 'Airport',
        'to_name' => 'Airport',
        'distance_km' => round($flightSegmentDistance, 2),
        'duration_min' => intval($travelTimeMin + $boardingBufferMin),
        'polyline' => []
    ],
    [
        'type' => 'road',
        'label' => 'Road from Airport',
        'from' => 'dest_airport',
        'to' => 'destination',
        'from_name' => 'Airport',
        'to_name' => 'Destination',
        'distance_km' => round($roadSegmentDistance, 2),
        'duration_min' => intval(($roadSegmentDistance / 40) * 60),
        'polyline' => []
    ]
];

// ========== RETURN UNIFIED CONTRACT ==========
$response = buildJourneyResponse(
    'flight',
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

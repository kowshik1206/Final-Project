<?php
/**
 * optimize.php - Multi-stop route optimization endpoint
 * POST /optimize/multi-stop
 * Implements Traveling Salesman Problem (TSP) using nearest neighbor heuristic
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

// Parse JSON body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    json_response(['ok' => false, 'error' => 'Invalid JSON'], 400);
}

$start = $input['start'] ?? null;
$end = $input['end'] ?? null;
$stops = $input['stops'] ?? [];

if (!$start || empty($stops)) {
    json_response(['ok' => false, 'error' => 'Missing start location or stops'], 400);
}

// Validate stops
if (count($stops) < 1) {
    json_response(['ok' => false, 'error' => 'At least one stop required'], 400);
}

// For simplicity, assume stops have lat/lng. In production, geocode location names.
foreach ($stops as $stop) {
    if (!isset($stop['lat']) || !isset($stop['lng'])) {
        json_response(['ok' => false, 'error' => 'All stops must have lat/lng'], 400);
    }
    if (!validate_coordinates($stop['lat'], $stop['lng'])) {
        json_response(['ok' => false, 'error' => 'Invalid coordinates in stops'], 400);
    }
}

// Simple nearest neighbor TSP solver
function nearest_neighbor_tsp($stops, $start_lat, $start_lng) {
    if (empty($stops)) {
        return ['ordered' => [], 'distance' => 0];
    }

    $unvisited = array_values($stops); // Re-index
    $ordered = [];
    $current_lat = $start_lat;
    $current_lng = $start_lng;
    $total_distance = 0;

    while (!empty($unvisited)) {
        $nearest_idx = 0;
        $nearest_dist = PHP_FLOAT_MAX;

        // Find nearest unvisited stop
        foreach ($unvisited as $idx => $stop) {
            $dist = haversine($current_lat, $current_lng, $stop['lat'], $stop['lng']);
            if ($dist < $nearest_dist) {
                $nearest_dist = $dist;
                $nearest_idx = $idx;
            }
        }

        $nearest = $unvisited[$nearest_idx];
        $ordered[] = $nearest;
        $total_distance += $nearest_dist;
        $current_lat = $nearest['lat'];
        $current_lng = $nearest['lng'];

        // Remove from unvisited
        array_splice($unvisited, $nearest_idx, 1);
    }

    return ['ordered' => $ordered, 'distance' => $total_distance];
}

// Haversine distance (km)
function haversine($lat1, $lng1, $lat2, $lng2) {
    $R = 6371; // Earth's radius in km
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}

// Parse start location (for now, assume it's in lat,lng format or use default Delhi coords)
$start_lat = 28.6139;
$start_lng = 77.209;

// If start is "lat,lng" format, parse it
if (strpos($start, ',') !== false) {
    $parts = explode(',', $start);
    if (count($parts) === 2) {
        $start_lat = (float)$parts[0];
        $start_lng = (float)$parts[1];
    }
}

// Run TSP solver
$result = nearest_neighbor_tsp($stops, $start_lat, $start_lng);

// Return optimized route
json_response([
    'ok' => true,
    'start_location' => $start,
    'end_location' => $end,
    'ordered_stops' => $result['ordered'],
    'total_distance_km' => round($result['distance'], 2),
    'stops_count' => count($stops),
], 200);

?>

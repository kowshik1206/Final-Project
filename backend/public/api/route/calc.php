<?php
/**
 * api/route/calc.php — Route calculation endpoint
 * POST /api/route/calc
 * Input: {"from":"lon,lat","to":"lon,lat"}
 * Output: {"ok":true,"distance_km":...,"duration_s":...}
 */

require_once __DIR__ . '/../../cors.php';

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate
if (!$input || !isset($input['from']) || !isset($input['to'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing from or to coordinates']);
    exit;
}

$from = $input['from'];
$to = $input['to'];

// Parse coordinates (lon,lat format)
$from_parts = explode(',', $from);
$to_parts = explode(',', $to);

if (count($from_parts) !== 2 || count($to_parts) !== 2) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid coordinate format. Use lon,lat']);
    exit;
}

$from_lon = (float)$from_parts[0];
$from_lat = (float)$from_parts[1];
$to_lon = (float)$to_parts[0];
$to_lat = (float)$to_parts[1];

// Validate ranges
if (abs($from_lat) > 90 || abs($from_lon) > 180 || abs($to_lat) > 90 || abs($to_lon) > 180) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid coordinate values']);
    exit;
}

// Haversine distance (km)
function haversine($lat1, $lon1, $lat2, $lon2) {
    $R = 6371; // Earth's radius in km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}

$distance_km = haversine($from_lat, $from_lon, $to_lat, $to_lon);
// Rough estimate: avg speed 60 km/h
$duration_s = ($distance_km / 60) * 3600;

http_response_code(200);
echo json_encode([
    'ok' => true,
    'distance_km' => round($distance_km, 2),
    'duration_s' => round($duration_s),
    'from' => $from,
    'to' => $to,
]);

?>

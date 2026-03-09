<?php
// backend/api/optimize_route.php
header('Content-Type: application/json; charset=utf-8');

/**
 * Helper function to return 422 Unprocessable Entity
 */
function error422($message) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'error' => $message
    ]);
    exit;
}

// 1. Input Parsing
$input = json_decode(file_get_contents("php://input"), true);
$startLoc = $input['start'] ?? null;
$endLoc = $input['end'] ?? null;
$rawStops = $input['stops'] ?? [];

// Validate start location exists
if (!$startLoc) {
    error422('Start location is required.');
}

// Validate stops array
if (!is_array($rawStops) || count($rawStops) === 0) {
    error422('At least one stop is required.');
}

/**
 * STRICT VALIDATION: Each stop must have valid latitude/longitude
 * Do NOT geocode names. Either coordinates exist or request fails.
 */
foreach ($rawStops as $i => $stop) {
    // Check lat/lng exist and are numeric
    if (
        !isset($stop['lat'], $stop['lng']) ||
        !is_numeric($stop['lat']) ||
        !is_numeric($stop['lng'])
    ) {
        error422('Stop #' . ($i + 1) . ' is missing valid latitude/longitude.');
    }
    
    // Validate lat/lng are within valid ranges
    $lat = floatval($stop['lat']);
    $lng = floatval($stop['lng']);
    
    if ($lat < -90 || $lat > 90) {
        error422('Stop #' . ($i + 1) . ': Invalid latitude (must be between -90 and 90).');
    }
    
    if ($lng < -180 || $lng > 180) {
        error422('Stop #' . ($i + 1) . ': Invalid longitude (must be between -180 and 180).');
    }
}

// 2. Geocoding Helper
function geocode($place) {
    // Basic caching could be added here, but avoiding complexity for now
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($place);
    $opts = ['http' => ['header' => "User-Agent: RouteIQ/1.0\r\n"]]; // Required by Nominatim
    $ctx = stream_context_create($opts);

    $json = @file_get_contents($url, false, $ctx);
    if (!$json) {
        error_log("[optimize_route.php] Geocode failed for: $place - No response from Nominatim");
        return null;
    }

    $data = json_decode($json, true);
    if (!$data || empty($data)) {
        error_log("[optimize_route.php] Geocode failed for: $place - Empty result");
        return null;
    }

    return [
        'lat' => floatval($data[0]['lat']),
        'lng' => floatval($data[0]['lon']),
        'name' => $place
    ];
}

// 3. Resolve Coordinates
$locations = [];
$mapIndexToInput = []; // to map OSRM result back to input names

// Start - use provided coordinates if available, otherwise geocode
if (isset($input['start_coords']) && isset($input['start_coords']['lat']) && isset($input['start_coords']['lng'])) {
    $geoStart = [
        'lat' => floatval($input['start_coords']['lat']),
        'lng' => floatval($input['start_coords']['lng']),
        'name' => $startLoc
    ];
} else {
    $geoStart = geocode($startLoc);
    if (!$geoStart) {
        error422('Could not find start location: ' . $startLoc . '. Please use a major city name.');
    }
}
$locations[] = $geoStart;
$mapIndexToInput[0] = ['type' => 'start', 'data' => $geoStart];

// Stops - use validated coordinates (already validated above)
foreach ($rawStops as $i => $s) {
    $locations[] = [
        'lat' => floatval($s['lat']),
        'lng' => floatval($s['lng']),
        'name' => $s['name'] ?? ('Stop ' . ($i + 1))
    ];
    $mapIndexToInput[count($locations) - 1] = ['type' => 'stop', 'data' => $s];
}

// End (Optional) - use provided coordinates if available, otherwise geocode
$hasEnd = false;
if ($endLoc) {
    if (isset($input['end_coords']) && isset($input['end_coords']['lat']) && isset($input['end_coords']['lng'])) {
        $geoEnd = [
            'lat' => floatval($input['end_coords']['lat']),
            'lng' => floatval($input['end_coords']['lng']),
            'name' => $endLoc
        ];
        $idx = count($locations);
        $locations[] = $geoEnd;
        $mapIndexToInput[$idx] = ['type' => 'end', 'data' => $geoEnd];
        $hasEnd = true;
    } else {
        $geoEnd = geocode($endLoc);
        if ($geoEnd) {
            $idx = count($locations);
            $locations[] = $geoEnd;
            $mapIndexToInput[$idx] = ['type' => 'end', 'data' => $geoEnd];
            $hasEnd = true;
        }
    }
}

// 4. Build OSRM URL
// Format: {lng},{lat};{lng},{lat}...
$coords = [];
foreach ($locations as $loc) {
    $coords[] = $loc['lng'] . ',' . $loc['lat'];
}
$coordString = implode(';', $coords);

// OSRM Trip API: http://router.project-osrm.org/trip/v1/driving/{coords}?parameters
$osrmUrl = "http://router.project-osrm.org/trip/v1/driving/{$coordString}";
$params = [
    'overview' => 'full',
    'geometries' => 'geojson',
    'source' => 'first',
    // if we have a distinct end point, dest=last. If not, roundtrip=true (default) usually implies back to start, but user might want open trip.
    // For RouteIQ context, if no end specified, usually implies round trip or just visit all. Let's assume roundtrip=true for no-end (circular),
    // and destination=last for specified end.
];

if ($hasEnd) {
    $params['destination'] = 'last';
    $params['roundtrip'] = 'false';
} else {
    // If no end provided, assume they want to return to start (TSP tour)
    $params['roundtrip'] = 'true';
    // Remove last semicolon if it was appended erroneously (logic ensures it matches $locations)
}

$query = http_build_query($params);
$requestUrl = $osrmUrl . '?' . $query;

// 5. Call OSRM
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $requestUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'RouteIQ/1.0'
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$resp) {
    echo json_encode(['ok'=>false, 'message'=>'Optimization service failed']); exit;
}

$osrmData = json_decode($resp, true);

if (!isset($osrmData['trips'][0])) {
    echo json_encode(['ok'=>false, 'message'=>'Could not find optimized path']); exit;
}

$trip = $osrmData['trips'][0];
$waypoints = $osrmData['waypoints']; // Sorted by visitation order

// 6. Map Response
// OSRM 'waypoints' array is sorted by visiting order.
// 'waypoint_index' is the index in the input coordinate string.
$orderedStops = [];
foreach ($waypoints as $wp) {
    $originalIdx = $wp['waypoint_index'];
    $originalData = $mapIndexToInput[$originalIdx];

    // We only care about the intermediate stops in the ordered list (and maybe ensuring start/end are right)
    // The frontend expects the *stops* list to be reordered.
    if ($originalData['type'] === 'stop') {
        $orderedStops[] = $originalData['data'];
    }
}

$polyline = [];
if (isset($trip['geometry']['coordinates'])) {
    foreach ($trip['geometry']['coordinates'] as $c) {
        $polyline[] = ['lat' => $c[1], 'lng' => $c[0]]; // GeoJSON is [lng, lat]
    }
}

echo json_encode([
    'ok' => true,
    'total_distance_km' => round(($trip['distance'] ?? 0) / 1000, 2),
    'ordered_stops' => $orderedStops,
    'polyline' => $polyline,
    'osm_raw' => $osrmData
]);
?>

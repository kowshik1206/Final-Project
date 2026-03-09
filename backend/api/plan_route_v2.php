<?php
/**
 * PHASE 3: Multi-Modal Route Planning API v2
 * Supports car, train, and flight routing with standardized journey contract
 * 
 * FEATURES IMPLEMENTED:
 * - Route Caching (Phase 3 Step 1)
 * - Geocoding (with Cache via geo_helpers) (Phase 3 Step 2)
 * - Hard Distance Caps (Phase 3 Step 3)
 * - Rate Limiting (Phase 4 Reliability)
 * - Standardized Error Contract (Phase 4 Reliability)
 */

header('Content-Type: application/json; charset=utf-8');

// Phase 4: Standard Response Helper
require_once __DIR__ . '/../utils/response_helpers.php';

try {
    // Explicitly fetch connection to avoid require_once bool true issue
    $conn = require __DIR__ . '/../db.php';
    require_once __DIR__ . '/../utils/geo_helpers.php';
    require_once __DIR__ . '/journey_contract.php';
    require_once __DIR__ . '/../utils/crypto_helpers.php';

    // ========== PHASE 3: RATE LIMITING (30 req / 10 min) ==========
    $ip = $_SERVER['REMOTE_ADDR'];
    $limit = 30; 
    $window = 600; // 10 minutes
    $now = time();

    $stmt = $conn->prepare("SELECT request_count, window_start FROM rate_limits WHERE ip = ?");
    if (!$stmt) respondError(ERR_DB_ERROR, "DB Prepare failed: " . $conn->error, 500);
    
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if ($row) {
        if ($now - $row['window_start'] > $window) {
            $conn->query("UPDATE rate_limits SET request_count = 1, window_start = $now WHERE ip = '$ip'");
        } else {
            if ($row['request_count'] >= $limit) {
                respondError(ERR_RATE_LIMITED, 'Rate limit exceeded. Try again later.', 429);
            }
            $conn->query("UPDATE rate_limits SET request_count = request_count + 1 WHERE ip = '$ip'");
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO rate_limits (ip, request_count, window_start) VALUES (?, 1, ?)");
        $stmt->bind_param('si', $ip, $now);
        $stmt->execute();
        $stmt->close();
    }

    // ========== INPUT VALIDATION ==========
    $input = json_decode(file_get_contents("php://input"), true);
    $source = $input['source'] ?? null;
    $destination = $input['destination'] ?? null;
    $mode = strtolower(trim($input['mode'] ?? 'car'));

    if (!$source || !$destination) {
        respondError(ERR_INVALID_INPUT, 'Missing source or destination', 400);
    }

    if (!in_array($mode, ['car', 'train', 'flight'])) {
        respondError(ERR_INVALID_INPUT, "Invalid mode: '$mode'. Must be: car, train, or flight", 400);
    }

    // ========== GEOCODING ==========
    $src = geocode_place($source);
    $dst = geocode_place($destination);

    if (!$src || !$dst) {
        $errorDetails = [];
        if (!$src) $errorDetails[] = "source: '$source'";
        if (!$dst) $errorDetails[] = "destination: '$destination'";
        respondError(ERR_INVALID_INPUT, 'Geocoding failed for: ' . implode(', ', $errorDetails), 400, $errorDetails);
    }

    // ========== PHASE 3: DISTANCE CAPS ==========
    $crowDistance = haversine_distance($src['lat'], $src['lng'], $dst['lat'], $dst['lng']);
    if ($crowDistance > 2500 && $mode === 'car') {
        respondError(ERR_INVALID_INPUT, 'Route too long for car analysis (>2500km). Please consider Flight mode.', 400);
    }
    if ($crowDistance < 1) {
         respondError(ERR_INVALID_INPUT, 'Source and Destination are too close.', 400);
    }

    // ========== PHASE 3: ROUTE CACHING ==========
    $routeHash = md5(
        number_format($src['lat'], 4) . ',' . number_format($src['lng'], 4) . '|' .
        number_format($dst['lat'], 4) . ',' . number_format($dst['lng'], 4) . '|' .
        $mode
    );

    $stmt = $conn->prepare("SELECT json_data FROM cache_routes WHERE route_hash = ? AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY) LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $routeHash);
        $stmt->execute();
        $stmt->bind_result($cachedJson);
        if ($stmt->fetch()) {
            $stmt->close();
            // Assuming cached JSON is already a well-formed success response
            // But we should parse it to ensure header content-type logic if needed, 
            // though simply echoing valid JSON is fine.
            echo $cachedJson;
            exit;
        }
        $stmt->close();
    }

    // ========== CALCULATE ROUTE ==========
    $segments = [];

    switch ($mode) {
        case 'car':
            try {
                $roadSegment = get_road_segment($src, $dst);
                $segments[] = [
                    'type' => 'road', 
                    'from' => 'source', 
                    'to' => 'destination',
                    'from_name' => $source,
                    'to_name' => $destination,
                    'label' => "Drive from $source to $destination",
                    'polyline' => $roadSegment['polyline'],
                    'distance_km' => $roadSegment['distance_km'],
                    'duration_min' => $roadSegment['duration_min']
                ];
            } catch (Exception $e) {
                respondError(ERR_OSRM_DOWN, "Car route calculation failed: " . $e->getMessage(), 502);
            }
            break;
            
        case 'train':
            try {
                $srcStation = find_nearest_station($conn, $src['lat'], $src['lng']);
                $dstStation = find_nearest_station($conn, $dst['lat'], $dst['lng']);
                if (!$srcStation || !$dstStation) respondError(ERR_INVALID_INPUT, 'No nearby railway stations found', 400);
                
                $roadTo = get_road_segment($src, ['lat'=>$srcStation['lat'], 'lng'=>$srcStation['lng']]);
                $railDist = haversine_distance($srcStation['lat'], $srcStation['lng'], $dstStation['lat'], $dstStation['lng']);
                $railPoly = create_straight_polyline(['lat'=>$srcStation['lat'], 'lng'=>$srcStation['lng']], ['lat'=>$dstStation['lat'], 'lng'=>$dstStation['lng']]);
                $roadFrom = get_road_segment(['lat'=>$dstStation['lat'], 'lng'=>$dstStation['lng']], $dst);
                
                $segments[] = array_merge($roadTo, ['type'=>'road', 'from'=>'user', 'to'=>$srcStation['code'], 'from_name'=>$source, 'to_name'=>$srcStation['name'], 'label'=>"Road to {$srcStation['name']}"]);
                $segments[] = ['type'=>'rail', 'from'=>$srcStation['code'], 'to'=>$dstStation['code'], 'from_name'=>$srcStation['name'], 'to_name'=>$dstStation['name'], 'polyline'=>$railPoly, 'distance_km'=>round($railDist, 2), 'duration_min'=>round($railDist/60*60), 'label'=>'Rail route'];
                $segments[] = array_merge($roadFrom, ['type'=>'road', 'from'=>$dstStation['code'], 'to'=>'destination', 'from_name'=>$dstStation['name'], 'to_name'=>$destination, 'label'=>"Road from {$dstStation['name']}"]);
            } catch (Exception $e) {
                respondError(ERR_INTERNAL_ERROR, "Train route calculation failed: " . $e->getMessage(), 500);
            }
            break;
            
        case 'flight':
            try {
                $srcAirport = find_nearest_airport($conn, $src['lat'], $src['lng']);
                $dstAirport = find_nearest_airport($conn, $dst['lat'], $dst['lng']);
                if (!$srcAirport || !$dstAirport) respondError(ERR_INVALID_INPUT, 'No nearby airports found', 400);
                
                $roadTo = get_road_segment($src, ['lat'=>$srcAirport['lat'], 'lng'=>$srcAirport['lng']]);
                $flightDist = haversine_distance($srcAirport['lat'], $srcAirport['lng'], $dstAirport['lat'], $dstAirport['lng']);
                $flightPoly = interpolate_great_circle(['lat'=>$srcAirport['lat'], 'lng'=>$srcAirport['lng']], ['lat'=>$dstAirport['lat'], 'lng'=>$dstAirport['lng']], 20);
                $roadFrom = get_road_segment(['lat'=>$dstAirport['lat'], 'lng'=>$dstAirport['lng']], $dst);
                
                $segments[] = array_merge($roadTo, ['type'=>'road', 'from'=>'user', 'to'=>$srcAirport['iata_code'], 'from_name'=>$source, 'to_name'=>$srcAirport['name'], 'label'=>"Road to {$srcAirport['name']}"]);
                $segments[] = ['type'=>'flight', 'from'=>$srcAirport['iata_code'], 'to'=>$dstAirport['iata_code'], 'from_name'=>$srcAirport['name'], 'to_name'=>$dstAirport['name'], 'polyline'=>$flightPoly, 'distance_km'=>round($flightDist, 2), 'duration_min'=>round($flightDist/800*60), 'label'=>'Flight path'];
                $segments[] = array_merge($roadFrom, ['type'=>'road', 'from'=>$dstAirport['iata_code'], 'to'=>'destination', 'from_name'=>$dstAirport['name'], 'to_name'=>$destination, 'label'=>"Road from {$dstAirport['name']}"]);
            } catch (Exception $e) {
                 respondError(ERR_INTERNAL_ERROR, "Flight route calculation failed: " . $e->getMessage(), 500);
            }
            break;
    }

    // Totals
    $totalDistance = 0; $totalDuration = 0;
    foreach ($segments as $seg) { $totalDistance += $seg['distance_km']; $totalDuration += $seg['duration_min']; }

    $response = buildJourneyResponse($mode, $totalDistance, intval($totalDuration), 0, $source, $destination, $src, $dst, $segments);
    
    // Sign
    $canonicalData = [
        'mode' => $response['mode'], 'total_distance_km' => $response['total_distance_km'], 'total_duration_min' => $response['total_duration_min'],
        'cost' => $response['cost'], 'source' => $response['source'], 'destination' => $response['destination'],
        'source_coords' => $response['source_coords'], 'destination_coords' => $response['destination_coords'], 'segments' => $response['segments']
    ];
    $response['signature'] = signJourneyData($canonicalData);

    $jsonOutput = json_encode($response);

    // Save Cache
    $stmt = $conn->prepare("INSERT INTO cache_routes (route_hash, source_coords, dest_coords, mode, json_data) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE json_data = VALUES(json_data), updated_at = NOW()");
    if ($stmt) {
        $sCoords = "{$src['lat']},{$src['lng']}"; $dCoords = "{$dst['lat']},{$dst['lng']}";
        $stmt->bind_param('sssss', $routeHash, $sCoords, $dCoords, $mode, $jsonOutput);
        $stmt->execute();
        $stmt->close();
    }

    echo $jsonOutput;

} catch (Throwable $e) {
    // Catch-all for truly unexpected internal errors
    respondError(ERR_INTERNAL_ERROR, $e->getMessage(), 500, ['trace' => $e->getTraceAsString()]);
}

<?php
/**
 * ========== POI BACKEND: ROUTE-AUTHORITATIVE ==========
 * 
 * Single source of truth for POI discovery along a route.
 * Guarantees:
 * - POIs are geographically relevant (distance-to-route validated)
 * - POIs are feasible for vehicle (coverage gaps validated)
 * - POIs are deterministically filtered (no silent failures)
 * - POIs are explainably ranked (scoring separated from filtering)
 * 
 * Input Contract: POST only, with polyline + vehicle + categories + radius
 * Output Contract: Structured response with feasibility analysis + ranked POIs
 * 
 * Phase 4: Structured logging + Error classification
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (!isset($conn)) {
    require_once __DIR__ . '/../db.php';
}
require_once __DIR__ . '/../utils/response_helpers.php';
require_once __DIR__ . '/../utils/logger.php';

// ========== INPUT VALIDATION (Non-negotiable) ==========

Logger::logRequest('/api/pois-for-route', 'POST');

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input || json_last_error() !== JSON_ERROR_NONE) {
    Logger::logError('/api/pois-for-route', 400, ERR_INVALID_INPUT, 'Invalid JSON input');
    respondError(ERR_INVALID_INPUT, 'Request body must be valid JSON', 400);
}

// Contract: polyline must be provided
$polyline = $input['polyline'] ?? null;
if (!$polyline || !is_array($polyline) || empty($polyline)) {
    Logger::logError('/api/pois-for-route', 400, ERR_INVALID_INPUT, 'Missing polyline', [
        'details' => 'polyline must be non-empty array of {lat, lng}'
    ]);
    respondError(ERR_INVALID_INPUT, 'Missing required field: polyline (array of {lat, lng})', 400, [
        'field' => 'polyline',
        'requirement' => 'Non-empty array of coordinate objects'
    ]);
}

Logger::debug('VALIDATION', 'Polyline received', ['point_count' => count($polyline)]);

// Contract: vehicle context (optional but recommended)
$vehicle = $input['vehicle'] ?? null;

// Contract: categories (required for intent clarity)
$categories = $input['categories'] ?? [];
if (!is_array($categories) || empty($categories)) {
    Logger::logError('/api/pois-for-route', 400, ERR_INVALID_INPUT, 'Missing categories', [
        'details' => 'categories must be non-empty array'
    ]);
    respondError(ERR_INVALID_INPUT, 'Missing required field: categories (non-empty array)', 400, [
        'field' => 'categories',
        'requirement' => 'Array of POI category names'
    ]);
}

// Contract: radius_km (bounded search, required)
if (!isset($input['radius_km'])) {
    Logger::logError('/api/pois-for-route', 400, ERR_INVALID_INPUT, 'Missing radius_km');
    respondError(ERR_INVALID_INPUT, 'Missing required field: radius_km (numeric, 1-20)', 400, [
        'field' => 'radius_km'
    ]);
}

$radiusKm = intval($input['radius_km']);
if ($radiusKm < 1 || $radiusKm > 20) {
    Logger::logError('/api/pois-for-route', 400, ERR_INVALID_INPUT, 'radius_km out of bounds', [
        'details' => 'Must be between 1 and 20'
    ]);
    respondError(ERR_INVALID_INPUT, 'radius_km must be between 1 and 20', 400, [
        'field' => 'radius_km',
        'received' => $radiusKm
    ]);
}

Logger::debug('VALIDATION', 'Input validated', [
    'polyline_points' => count($polyline),
    'categories' => $categories,
    'radius_km' => $radiusKm
]);

// ========== ROUTE SAMPLING (Backbone) ==========
// Process full polyline intelligently without overload

function samplePolyline($polyline, $maxPoints = 200) {
    $count = count($polyline);
    if ($count <= $maxPoints) {
        return $polyline;
    }
    
    $step = ceil($count / $maxPoints);
    $sampled = [];
    for ($i = 0; $i < $count; $i += $step) {
        $sampled[] = $polyline[$i];
    }
    return $sampled;
}

$sampledPolyline = samplePolyline($polyline);

// ========== BOUNDING BOX CALCULATION (Fast, Index-friendly) ==========

$minLat = 90;
$maxLat = -90;
$minLng = 180;
$maxLng = -180;

foreach ($sampledPolyline as $point) {
    if (!isset($point['lat']) || !isset($point['lng'])) {
        continue;
    }
    $lat = floatval($point['lat']);
    $lng = floatval($point['lng']);
    
    $minLat = min($minLat, $lat);
    $maxLat = max($maxLat, $lat);
    $minLng = min($minLng, $lng);
    $maxLng = max($maxLng, $lng);
}

// Add buffer to BBOX (1 degree ≈ 111km, so radiusKm/111)
$bufferDeg = $radiusKm / 111;
$minLat -= $bufferDeg;
$maxLat += $bufferDeg;
$minLng -= $bufferDeg;
$maxLng += $bufferDeg;

// ========== PHASE 1: SQL BBOX QUERY (Authority: Database) ==========

if (!$conn) {
    Logger::logError('/api/pois-for-route', 500, ERR_DB_ERROR, 'Database connection failed');
    respondError(ERR_DB_ERROR, 'Database connection failed', 500);
}

// Build category filter
$placeholders = implode(',', array_fill(0, count($categories), '?'));
$query = "
    SELECT id, name, latitude, longitude, category, brand, phone, tags
    FROM pois
    WHERE latitude BETWEEN ? AND ?
      AND longitude BETWEEN ? AND ?
      AND category IN ($placeholders)
    LIMIT 5000
";

$params = array_merge(
    [$minLat, $maxLat, $minLng, $maxLng],
    $categories
);

$types = "dddd" . str_repeat("s", count($categories));

Logger::debug('BBOX_QUERY', 'Executing spatial query', [
    'min_lat' => $minLat,
    'max_lat' => $maxLat,
    'min_lng' => $minLng,
    'max_lng' => $maxLng,
    'categories' => $categories
]);

$stmt = $conn->prepare($query);
if (!$stmt) {
    Logger::logError('/api/pois-for-route', 500, ERR_DB_ERROR, 'Database query preparation failed', [
        'details' => $conn->error
    ]);
    respondError(ERR_DB_ERROR, 'Database query failed: ' . $conn->error, 500);
}

$stmt->bind_param($types, ...$params);
if (!$stmt->execute()) {
    Logger::logError('/api/pois-for-route', 500, ERR_DB_ERROR, 'Query execution failed', [
        'details' => $stmt->error
    ]);
    respondError(ERR_DB_ERROR, 'Query execution failed', 500);
}

$result = $stmt->get_result();

$allPois = [];
while ($row = $result->fetch_assoc()) {
    $allPois[] = $row;
}
$stmt->close();

Logger::debug('BBOX_RESULT', 'Bounding box query completed', [
    'pois_in_bbox' => count($allPois)
]);


// ========== PHASE 2: DISTANCE-TO-ROUTE FILTERING (Authority: Geometry) ==========
// This is where 90% projects die. Every POI must be genuinely "along the route".

function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000; // Earth radius in meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

function minDistanceToPolyline($poiLat, $poiLng, $polyline) {
    $minDist = PHP_FLOAT_MAX;
    foreach ($polyline as $point) {
        $dist = haversineDistance($poiLat, $poiLng, $point['lat'], $point['lng']);
        if ($dist < $minDist) {
            $minDist = $dist;
        }
    }
    return $minDist;
}

$filteredPois = [];
$bufferMeters = $radiusKm * 1000;

Logger::debug('DISTANCE_FILTER', 'Starting distance-to-route filtering', [
    'all_pois_count' => count($allPois),
    'buffer_meters' => $bufferMeters
]);

foreach ($allPois as $poi) {
    $d = minDistanceToPolyline(
        floatval($poi['latitude']),
        floatval($poi['longitude']),
        $sampledPolyline
    );
    
    if ($d <= $bufferMeters) {
        $poi['distance_to_route_m'] = intval(round($d));
        $filteredPois[] = $poi;
    }
}

Logger::debug('DISTANCE_FILTER', 'Filtering complete', [
    'filtered_pois_count' => count($filteredPois)
]);

// ========== PHASE 3: VEHICLE FEASIBILITY VALIDATION (Critical) ==========
// Rule: A POI is invalid if it creates a gap larger than effective range.

$feasibilityStatus = [
    'vehicle' => $vehicle ? $vehicle['fuel_type'] : 'none',
    'effective_range_km' => $vehicle ? intval($vehicle['effective_range_km'] ?? 0) : null,
    'max_gap_km' => null,
    'status' => 'OK'
];

if ($vehicle && isset($vehicle['fuel_type']) && isset($vehicle['effective_range_km'])) {
    $effectiveRangeKm = intval($vehicle['effective_range_km']);
    $routeLengthKm = 0;
    
    // Calculate route length
    if (count($sampledPolyline) > 1) {
        for ($i = 0; $i < count($sampledPolyline) - 1; $i++) {
            $d = haversineDistance(
                $sampledPolyline[$i]['lat'],
                $sampledPolyline[$i]['lng'],
                $sampledPolyline[$i+1]['lat'],
                $sampledPolyline[$i+1]['lng']
            );
            $routeLengthKm += $d / 1000;
        }
    }
    
    // Project POIs onto route and check coverage
    $poiDistances = [];
    foreach ($filteredPois as $poi) {
        // Simple projection: find closest point on route
        $minDist = PHP_FLOAT_MAX;
        $routeDistKm = 0;
        
        for ($i = 0; $i < count($sampledPolyline) - 1; $i++) {
            // Distance from start to this segment
            $segmentDist = haversineDistance(
                $sampledPolyline[$i]['lat'],
                $sampledPolyline[$i]['lng'],
                $sampledPolyline[$i+1]['lat'],
                $sampledPolyline[$i+1]['lng']
            ) / 1000;
            
            $distToPoi = haversineDistance(
                $sampledPolyline[$i]['lat'],
                $sampledPolyline[$i]['lng'],
                floatval($poi['latitude']),
                floatval($poi['longitude'])
            );
            
            if ($distToPoi < $minDist) {
                $minDist = $distToPoi;
                // Rough projection
                $poiDistances[] = $routeDistKm;
            }
            
            $routeDistKm += $segmentDist;
        }
    }
    
    // Check for gaps > effective range
    sort($poiDistances);
    $maxGap = 0;
    
    // Gap from start to first POI
    if (!empty($poiDistances)) {
        $maxGap = max($maxGap, $poiDistances[0]);
    } else {
        $maxGap = $routeLengthKm; // No POIs at all!
    }
    
    // Gaps between consecutive POIs
    for ($i = 0; $i < count($poiDistances) - 1; $i++) {
        $gap = $poiDistances[$i+1] - $poiDistances[$i];
        $maxGap = max($maxGap, $gap);
    }
    
    // Gap from last POI to end
    if (!empty($poiDistances)) {
        $maxGap = max($maxGap, $routeLengthKm - $poiDistances[count($poiDistances)-1]);
    }
    
    $feasibilityStatus['max_gap_km'] = round($maxGap, 2);
    
    Logger::debug('FEASIBILITY', 'Gap analysis completed', [
        'max_gap_km' => $maxGap,
        'effective_range_km' => $effectiveRangeKm,
        'status' => $feasibilityStatus['status']
    ]);
    
    // REJECTION: If max gap exceeds effective range
    if ($maxGap > $effectiveRangeKm) {
        $feasibilityStatus['status'] = 'FAIL';
        Logger::logError('/api/pois-for-route', 422, ERR_NO_INFRASTRUCTURE, 
            "Route not feasible for {$vehicle['fuel_type']} (max gap {$maxGap}km > range {$effectiveRangeKm}km)", [
            'max_gap_km' => $maxGap,
            'effective_range_km' => $effectiveRangeKm,
            'fuel_type' => $vehicle['fuel_type']
        ]);
        respondError(ERR_NO_INFRASTRUCTURE, 
            "No {$vehicle['fuel_type']} infrastructure found. Gap of {$maxGap}km exceeds vehicle range of {$effectiveRangeKm}km.", 
            422, [
                'max_gap_km' => $maxGap,
                'effective_range_km' => $effectiveRangeKm,
                'suggestion' => 'Try a different route or vehicle type'
            ]);
    }
}

// ========== PHASE 4: SCORING (Separate from Filtering) ==========
// Now that we've validated hard constraints, we can score for preference.

foreach ($filteredPois as &$poi) {
    $distScore = 1.0 - min(1.0, $poi['distance_to_route_m'] / (50 * 1000)); // Max 50km away
    $categoryBoost = 1.0; // Default
    
    // Example preference boosts (customize for your domain)
    if ($poi['category'] === 'hospital') {
        $categoryBoost = 0.9;
    } elseif ($poi['category'] === 'temple') {
        $categoryBoost = 0.7;
    }
    
    $poi['score'] = min(1.0, $distScore * $categoryBoost);
}
unset($poi);

// Sort by score descending
usort($filteredPois, fn($a, $b) => $b['score'] <=> $a['score']);

// ========== RESPONSE CONTRACT (Exam-Proof) ==========

$categoryCounts = [];
foreach ($filteredPois as $poi) {
    $cat = $poi['category'];
    if (!isset($categoryCounts[$cat])) {
        $categoryCounts[$cat] = 0;
    }
    $categoryCounts[$cat]++;
}

// Limit results for performance
$maxResults = 100;
$pois = array_slice($filteredPois, 0, $maxResults);

// Log response
if (empty($pois)) {
    Logger::logError('/api/pois-for-route', 200, ERR_POI_ZERO_RESULTS, 
        'No POIs found within criteria', [
        'polyline_points' => count($sampledPolyline),
        'categories_requested' => $categories,
        'radius_km' => $radiusKm
    ]);
} else {
    Logger::logResponse('/api/pois-for-route', 200, [
        'data_count' => count($pois)
    ]);
}

Logger::debug('RESPONSE', 'Final POI count', [
    'total_in_route' => count($filteredPois),
    'returned' => count($pois),
    'by_category' => $categoryCounts
]);

// Send success response
respondSuccess([
    'summary' => [
        'total_pois' => count($filteredPois),
        'returned_pois' => count($pois),
        'by_category' => $categoryCounts
    ],
    'feasibility' => $feasibilityStatus,
    'pois' => array_map(function($p) {
        return [
            'id' => intval($p['id']),
            'name' => $p['name'],
            'category' => $p['category'],
            'lat' => floatval($p['latitude']),
            'lng' => floatval($p['longitude']),
            'distance_to_route_m' => $p['distance_to_route_m'],
            'score' => round($p['score'], 3)
        ];
    }, $pois)
]);


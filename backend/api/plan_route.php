<?php
header('Content-Type: application/json; charset=utf-8');

// ========== REQUIRES ==========
require_once __DIR__ . '/../public/db.php';
require_once __DIR__ . '/../utils/geometry.php';

// ========== INPUT ==========
$input = json_decode(file_get_contents("php://input"), true);
$source = $input['source'] ?? null;
$destination = $input['destination'] ?? null;
$vehicle = $input['vehicle'] ?? null; // NEW: Vehicle data for range-aware routing

if (!$source || !$destination) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'message'=>'Missing source or destination']);
    exit;
}

// ========== GEOCODER WITH CACHE AND NOMINATIM FALLBACK ==========
function geocode($place) {
    // City aliases for common Indian city names
    $cityAliases = [
        'vizag' => 'Visakhapatnam',
        'vizagpatnam' => 'Visakhapatnam',
        'kolkata' => 'Calcutta',
        'bangalore' => 'Bengaluru',
        'bengaluru' => 'Bengaluru',
        'bombay' => 'Mumbai',
        'pune' => 'Puna',
        'nagpur' => 'Nagpur',
        'coimbatore' => 'Coimbatore',
        'lucknow' => 'Lucknow',
        'kanpur' => 'Kanpur',
        'chandigarh' => 'Chandigarh',
        'bhopal' => 'Bhopal',
        'indore' => 'Indore',
        'ahmedabad' => 'Ahmedabad',
        'surat' => 'Surat',
        'vadodara' => 'Vadodara',
        'jaipur' => 'Jaipur',
        'lucknow' => 'Lucknow',
        'agra' => 'Agra',
        'varanasi' => 'Varanasi',
        'allahabad' => 'Allahabad',
        'patna' => 'Patna',
        'ranchi' => 'Ranchi',
        'guwahati' => 'Guwahati',
        'shillong' => 'Shillong',
        'kochi' => 'Kochi',
        'thiruvananthapuram' => 'Thiruvananthapuram',
        'trivandrum' => 'Thiruvananthapuram',
        'kottayam' => 'Kottayam',
        'thrissur' => 'Thrissur',
        'kozhikode' => 'Kozhikode',
        'calicut' => 'Kozhikode',
        'kannur' => 'Kannur',
        'kasaragod' => 'Kasaragod'
    ];
    
    // Apply alias mapping
    $normalizedPlace = strtolower(trim($place));
    if (isset($cityAliases[$normalizedPlace])) {
        $place = $cityAliases[$normalizedPlace];
    }
    
    try {
        // First, try database cache
        $db = get_db_connection();
        $queryHash = md5(strtolower($place));
        
        $stmt = $db->prepare("SELECT lat, lng, display_name FROM cache_geocoding WHERE query_hash = ?");
        $stmt->execute([$queryHash]);
        $cached = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cached) {
            return [
                'lat' => floatval($cached['lat']),
                'lng' => floatval($cached['lng']),
                'source' => 'cache'
            ];
        }
    } catch (Exception $e) {
        // Database not available, proceed to Nominatim
    }
    
    // Nominatim geocoding
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($place . ", India");
    $opts = ['http' => ['header' => "User-Agent: RouteIQ/1.0\r\n"]];
    $ctx = stream_context_create($opts);

    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return null;

    $data = json_decode($json, true);
    if (!$data || empty($data)) return null;

    $result = [
        'lat' => floatval($data[0]['lat']),
        'lng' => floatval($data[0]['lon']),
        'source' => 'nominatim'
    ];
    
    // Cache the result
    try {
        $db = get_db_connection();
        $queryHash = md5(strtolower($place));
        $stmt = $db->prepare("
            INSERT INTO cache_geocoding (query_hash, query_text, lat, lng, display_name, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");
        $stmt->execute([
            $queryHash,
            $place,
            $result['lat'],
            $result['lng'],
            $data[0]['display_name'] ?? $place
        ]);
    } catch (Exception $e) {
        // Cache write failed, but we still have the result
    }
    
    return $result;
}

$src = geocode($source);
$dst = geocode($destination);

if (!$src) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'message'=>"Unable to resolve coordinates for: source: $source", 'error_code' => 'GEOCODE_SOURCE_FAILED']);
    exit;
}

if (!$dst) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'message'=>"Unable to resolve coordinates for: destination: $destination", 'error_code' => 'GEOCODE_DEST_FAILED']);
    exit;
}

// ========== OSRM ROUTING ==========
$routeURL = "http://router.project-osrm.org/route/v1/driving/{$src['lng']},{$src['lat']};{$dst['lng']},{$dst['lat']}?overview=full&geometries=geojson";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $routeURL,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 60, // Increased to 60s for slow OSRM demo server
    CURLOPT_USERAGENT => 'RouteIQ/1.0',
    CURLOPT_FOLLOWLOCATION => true
]);

$routeJson = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// file_put_contents(__DIR__ . "/osrm_debug.json", "HTTP Code: $httpCode\nError: $curlError\nResponse: " . ($routeJson ?: "NO RESPONSE"));

if ($httpCode !== 200 || !$routeJson) {
    error_log("OSRM Failed: $httpCode, $curlError");
    echo json_encode(['ok'=>false, 'message'=>'OSRM request failed', 'debug_error' => $curlError, 'http_code' => $httpCode]);
    exit;
}

$routeData = json_decode($routeJson, true);

if (!isset($routeData['routes'][0])) {
    echo json_encode(['ok'=>false, 'message'=>'OSRM returned no routes', 'raw'=>$routeData]);
    exit;
}

$route = $routeData['routes'][0];

// always return valid numbers
$distanceKm = isset($route['distance']) ? round($route['distance'] / 1000, 2) : 0;
$durationMin = isset($route['duration']) ? round($route['duration'] / 60) : 0;

// ========== POLYLINE PROCESSING ==========
$polyline = [];
if (isset($route['geometry']['coordinates'])) {
    foreach ($route['geometry']['coordinates'] as $c) {
        $polyline[] = ['lat'=>$c[1], 'lng'=>$c[0]];
    }
}

// Optimize polyline if too large (> 500 points)
if (count($polyline) > 500) {
    // 0.0001 degrees is roughly 11 meters precision
    $polyline = ramer_douglas_peucker($polyline, 0.0005); // ~50m precision for map view
}

// ========== VEHICLE-AWARE ROUTING ANALYSIS ==========
$vehicleAnalysis = null;

if ($vehicle && isset($vehicle['fuel_type']) && isset($vehicle['range_km'])) {
    $safetyFactor = 0.8; // Conservative: don't use 100% of range
    $effectiveRangeKm = intval($vehicle['range_km'] * $safetyFactor);
    
    // Calculate mandatory stops needed
    $stopsRequired = max(0, ceil($distanceKm / $effectiveRangeKm) - 1);
    
    $vehicleAnalysis = [
        'fuel_type' => $vehicle['fuel_type'],
        'vehicle_range_km' => $vehicle['range_km'],
        'effective_range_km' => $effectiveRangeKm,
        'route_distance_km' => $distanceKm,
        'stops_required' => $stopsRequired,
        'feasible' => true, // Will be updated if validation fails
        'warning' => null
    ];
    
    // ========== FEASIBILITY CHECK: Validate stop availability ==========
    // Determine required stop type
    $stopType = match($vehicle['fuel_type']) {
        'electric' => 'charger',
        'ev' => 'charger',
        'cng' => 'cng',
        default => 'fuel' // petrol, diesel
    };
    
    // If stops are required, validate they exist
    if ($stopsRequired > 0 && !empty($polyline)) {
        // Call internal POI check
        $poiCheckResult = checkStopsAvailable($polyline, $stopType, $effectiveRangeKm);
        
        if (!$poiCheckResult['available']) {
            http_response_code(422); // Unprocessable Entity
            echo json_encode([
                'ok' => false,
                'error' => 'Route not feasible for selected vehicle',
                'reason' => "No {$stopType} stations found within {$effectiveRangeKm} km range",
                'suggestion' => "Try a different route or vehicle type",
                'vehicle_analysis' => $vehicleAnalysis
            ]);
            exit;
        }
        
        // Add suggestion if stops are tight
        if ($poiCheckResult['gap_found'] > $effectiveRangeKm * 0.9) {
            $vehicleAnalysis['warning'] = "Limited stops available. Route is feasible but stops are sparse.";
        }
    }
}

// ========== HELPER FUNCTION: Check stop availability ==========
function checkStopsAvailable($polyline, $stopType, $maxGapKm) {
    // Quick check: do we have POI database available?
    // For now, return optimistic result
    // In production, query your POI database here
    
    return [
        'available' => true,
        'gap_found' => $maxGapKm * 0.7 // Assume reasonable gaps
    ];
}

echo json_encode([
    'ok' => true,
    'source_coords' => $src,
    'destination_coords' => $dst,
    'distance_km' => $distanceKm,
    'duration_min' => $durationMin,
    'polyline' => $polyline,
    'vehicle_analysis' => $vehicleAnalysis // NEW: Vehicle feasibility info
]);

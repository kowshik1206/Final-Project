<?php
/**
 * plan_route_train.php
 * Train-specific route planning with multi-segment logic
 * 
 * POST /api/plan-route-train
 * Body: {
 *   source: "city_name",
 *   destination: "city_name",
 *   source_coords?: {lat, lng},
 *   destination_coords?: {lat, lng}
 * }
 */

// Use the public PDO-based DB loader which exposes get_db_connection()
require_once __DIR__ . '/../public/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

const MAX_STATION_SEARCH_RADIUS_KM = 50;
const ROAD_DISTANCE_MULTIPLIER = 1.3;
const ROAD_SPEED_KMH = 60;
const RAIL_SPEED_KMH = 55;  // Indian express trains average ~55 km/h including stops
const STATION_WAITING_TIME_MIN = 30;  // Average waiting time at stations

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST only']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $source = $input['source'] ?? null;
    $destination = $input['destination'] ?? null;
    $userSourceCoords = $input['source_coords'] ?? null;
    $userDestCoords = $input['destination_coords'] ?? null;
    
    // Debug logging
    error_log('=== TRAIN ROUTE REQUEST ===');
    error_log('Source: ' . var_export($source, true));
    error_log('Destination: ' . var_export($destination, true));
    error_log('Input: ' . json_encode($input));
    
    // Validate inputs
    if (!$source || !$destination) {
        throw new Exception('Missing source or destination');
    }
    
    // Parse coordinates
    $sourceCoords = parseLocationInput($source, $userSourceCoords);
    $destCoords = parseLocationInput($destination, $userDestCoords);

    // If parsing failed (we received plain city names), attempt to resolve via DB (railway_stations)
    if (!$sourceCoords || !$destCoords) {
        $db_for_resolve = get_db_connection();
        if (!$sourceCoords) {
            $sourceCoords = resolveCoordsFromName($db_for_resolve, $source);
            if (!$sourceCoords) {
                $sourceCoords = geocodeViaNominatim($source);
            }
        }
        if (!$destCoords) {
            $destCoords = resolveCoordsFromName($db_for_resolve, $destination);
            if (!$destCoords) {
                $destCoords = geocodeViaNominatim($destination);
            }
        }
    }

    if (!$sourceCoords || !$destCoords) {
        $missing = [];
        if (!$sourceCoords) $missing[] = "source: {$source}";
        if (!$destCoords) $missing[] = "destination: {$destination}";
        throw new Exception('Unable to resolve coordinates for: ' . implode(', ', $missing));
    }
    
    // Main routing logic
    $result = planTrainRoute($sourceCoords, $destCoords, $source, $destination);
    
    http_response_code($result['ok'] ? 200 : 400);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}

// ============================================================================
// CORE ALGORITHM
// ============================================================================

function planTrainRoute(array $sourceCoords, array $destCoords, $sourceName, $destName) {
    $db = get_db_connection();
    
    // Step 1: Find nearest railway stations
    $sourceStation = findNearestRailwayStation($db, $sourceCoords);
    $destStation = findNearestRailwayStation($db, $destCoords);
    
    if (!$sourceStation || !$destStation) {
        return [
            'ok' => false,
            'error' => 'No railway stations found within ' . MAX_STATION_SEARCH_RADIUS_KM . ' km of route'
        ];
    }
    
    // Step 2: Validate stations are different
    if ($sourceStation['code'] === $destStation['code']) {
        return [
            'ok' => false,
            'error' => 'Source and destination resolve to same station'
        ];
    }
    
    // Step 3: Build segments
    $segments = [];
    
    // Segment 1: Road from source to source station
    $roadToStationDistance = calculateRoadDistance(
        $sourceCoords['lat'],
        $sourceCoords['lng'],
        $sourceStation['latitude'],
        $sourceStation['longitude']
    );
    
    if ($roadToStationDistance > MAX_STATION_SEARCH_RADIUS_KM) {
        return [
            'ok' => false,
            'error' => "Source too far from nearest station ({$roadToStationDistance} km)"
        ];
    }
    
    $segments[] = [
        'type' => 'road',
        'label' => 'Road to Station',
        'from' => cleanCityName($sourceName),
        'to' => $sourceStation['code'],
        'from_name' => 'Source',
        'to_name' => $sourceStation['name'],
        'distance_km' => round($roadToStationDistance, 2),
        'duration_min' => round(($roadToStationDistance / ROAD_SPEED_KMH) * 60),
        'polyline' => generateRoadPolyline(
            $sourceCoords['lat'],
            $sourceCoords['lng'],
            $sourceStation['latitude'],
            $sourceStation['longitude']
        )
    ];
    
    // Segment 2: Rail between stations
    $railDistance = calculateHaversineDistance(
        $sourceStation['latitude'],
        $sourceStation['longitude'],
        $destStation['latitude'],
        $destStation['longitude']
    );
    
    $segments[] = [
        'type' => 'rail',
        'label' => 'Train Journey',
        'from' => $sourceStation['code'],
        'to' => $destStation['code'],
        'from_name' => $sourceStation['name'],
        'to_name' => $destStation['name'],
        'distance_km' => round($railDistance, 2),
        'duration_min' => round(($railDistance / RAIL_SPEED_KMH) * 60) + STATION_WAITING_TIME_MIN,
        'polyline' => generateRailPolyline(
            $sourceStation['latitude'],
            $sourceStation['longitude'],
            $destStation['latitude'],
            $destStation['longitude']
        )
    ];
    
    // Segment 3: Road from destination station to destination
    $roadFromStationDistance = calculateRoadDistance(
        $destStation['latitude'],
        $destStation['longitude'],
        $destCoords['lat'],
        $destCoords['lng']
    );
    
    if ($roadFromStationDistance > MAX_STATION_SEARCH_RADIUS_KM) {
        return [
            'ok' => false,
            'error' => "Destination too far from nearest station ({$roadFromStationDistance} km)"
        ];
    }
    
    $segments[] = [
        'type' => 'road',
        'label' => 'Road from Station',
        'from' => $destStation['code'],
        'to' => cleanCityName($destName),
        'from_name' => $destStation['name'],
        'to_name' => 'Destination',
        'distance_km' => round($roadFromStationDistance, 2),
        'duration_min' => round(($roadFromStationDistance / ROAD_SPEED_KMH) * 60),
        'polyline' => generateRoadPolyline(
            $destStation['latitude'],
            $destStation['longitude'],
            $destCoords['lat'],
            $destCoords['lng']
        )
    ];
    
    // Step 4: Calculate totals
    $totalDistance = array_sum(array_column($segments, 'distance_km'));
    $totalDuration = array_sum(array_column($segments, 'duration_min'));
    
    // Step 5: Build full polyline
    $fullPolyline = array_merge(
        $segments[0]['polyline'],
        $segments[1]['polyline'],
        $segments[2]['polyline']
    );
    
    return [
        'ok' => true,
        'mode' => 'train',
        'source' => $sourceName,
        'destination' => $destName,
        'total_distance_km' => round($totalDistance, 2),
        'total_duration_min' => round($totalDuration),
        'cost' => 0, // Cost can be calculated separately if needed
        'source_coords' => ['lat' => $sourceCoords['lat'], 'lng' => $sourceCoords['lng']],
        'destination_coords' => ['lat' => $destCoords['lat'], 'lng' => $destCoords['lng']],
        'segments' => $segments,
        'polyline' => $fullPolyline,
        'source_station' => $sourceStation,
        'dest_station' => $destStation
    ];
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function findNearestRailwayStation($db, array $coords) {
    $sql = "
        SELECT 
            id,
            code,
            name,
            city,
            latitude,
            longitude,
            importance,
            ROUND(
                (111.111 * DEGREES(ACOS(
                    LEAST(1.0,
                        COS(RADIANS(latitude)) *
                        COS(RADIANS(?)) *
                        COS(RADIANS(longitude) - RADIANS(?)) +
                        SIN(RADIANS(latitude)) *
                        SIN(RADIANS(?))
                    )
                )))
            , 2) as distance_km
        FROM railway_stations
        WHERE importance IN ('major', 'junction')
        HAVING distance_km <= ?
        ORDER BY distance_km ASC
        LIMIT 1
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $coords['lat'],
        $coords['lng'],
        $coords['lat'],
        MAX_STATION_SEARCH_RADIUS_KM
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) return $result;

    // Fallback: return nearest station regardless of importance or radius
    $sql2 = "
        SELECT 
            id, code, name, city, latitude, longitude, importance,
            ROUND((111.111 * DEGREES(ACOS(LEAST(1.0,
                COS(RADIANS(latitude)) * COS(RADIANS(?)) * COS(RADIANS(longitude) - RADIANS(?)) +
                SIN(RADIANS(latitude)) * SIN(RADIANS(?))
            )))), 2) as distance_km
        FROM railway_stations
        ORDER BY distance_km ASC
        LIMIT 1
    ";
    $stmt2 = $db->prepare($sql2);
    $stmt2->execute([
        $coords['lat'],
        $coords['lng'],
        $coords['lat']
    ]);
    $res2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    return $res2 ?: null;
}

function calculateRoadDistance($lat1, $lng1, $lat2, $lng2) {
    // Straight-line distance × multiplier for road curvature
    $straightLine = calculateHaversineDistance($lat1, $lng1, $lat2, $lng2);
    return $straightLine * ROAD_DISTANCE_MULTIPLIER;
}

function calculateHaversineDistance($lat1, $lng1, $lat2, $lng2) {
    $R = 6371; // Earth radius in km
    
    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $deltaLat = deg2rad($lat2 - $lat1);
    $deltaLng = deg2rad($lng2 - $lng1);
    
    $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLng / 2) * sin($deltaLng / 2);
    
    $c = 2 * asin(sqrt($a));
    
    return $R * $c;
}

function generateRoadPolyline($lat1, $lng1, $lat2, $lng2) {
    // Simple 2-point polyline for road segments
    return [
        ['lat' => (float)$lat1, 'lng' => (float)$lng1],
        ['lat' => (float)$lat2, 'lng' => (float)$lng2]
    ];
}

function generateRailPolyline($lat1, $lng1, $lat2, $lng2) {
    // Great-circle arc approximation with 5 intermediate points
    $points = [
        ['lat' => (float)$lat1, 'lng' => (float)$lng1]
    ];
    
    for ($i = 1; $i <= 4; $i++) {
        $t = $i / 5;
        $points[] = [
            'lat' => (float)($lat1 + ($lat2 - $lat1) * $t),
            'lng' => (float)($lng1 + ($lng2 - $lng1) * $t)
        ];
    }
    
    $points[] = ['lat' => (float)$lat2, 'lng' => (float)$lng2];
    
    return $points;
}

function parseLocationInput($input, $userCoords) {
    // If user provided coordinates, use them
    if ($userCoords && isset($userCoords['lat'], $userCoords['lng'])) {
        return [
            'lat' => (float)$userCoords['lat'],
            'lng' => (float)$userCoords['lng']
        ];
    }
    
    // If input looks like coordinates (lat,lng), parse them
    if (is_string($input) && strpos($input, ',') !== false) {
        list($lat, $lng) = array_map('trim', explode(',', $input));
        if (is_numeric($lat) && is_numeric($lng)) {
            return [
                'lat' => (float)$lat,
                'lng' => (float)$lng
            ];
        }
    }
    
    return null;
}

function resolveCoordsFromName($db, $name) {
    // IMPORTANT: This function returns CITY coordinates, not station coordinates!
    // Do NOT use railway_stations table for this purpose.
    
    error_log('Resolving coords for city: ' . var_export($name, true));
    
    // Map of known Indian cities to their approximate center coordinates
    $cityCoordinates = [
        'delhi' => ['lat' => 28.6139, 'lng' => 77.2090],
        'mumbai' => ['lat' => 19.0760, 'lng' => 72.8777],
        'bangalore' => ['lat' => 12.9716, 'lng' => 77.5946],
        'bengaluru' => ['lat' => 12.9716, 'lng' => 77.5946],
        'chennai' => ['lat' => 13.0827, 'lng' => 80.2707],
        'kolkata' => ['lat' => 22.5726, 'lng' => 88.3639],
        'hyderabad' => ['lat' => 17.3850, 'lng' => 78.4867],
        'pune' => ['lat' => 18.5204, 'lng' => 73.8567],
        'jaipur' => ['lat' => 26.9124, 'lng' => 75.7873],
        'ahmedabad' => ['lat' => 23.0225, 'lng' => 72.5714],
        'lucknow' => ['lat' => 26.8467, 'lng' => 80.9462],
        'indore' => ['lat' => 22.7196, 'lng' => 75.8577],
        'surat' => ['lat' => 21.1458, 'lng' => 72.1937],
        'kochi' => ['lat' => 9.9312, 'lng' => 76.2673],
        'goa' => ['lat' => 15.2993, 'lng' => 73.8243],
        'madgaon' => ['lat' => 15.2993, 'lng' => 73.8243],
        'visakhapatnam' => ['lat' => 17.6869, 'lng' => 83.2185],
        'guwahati' => ['lat' => 26.1445, 'lng' => 91.7362],
        'bhopal' => ['lat' => 23.1815, 'lng' => 79.9864],
        'nagpur' => ['lat' => 21.1458, 'lng' => 79.0882],
        'chandigarh' => ['lat' => 30.7333, 'lng' => 76.8277],
        'ludhiana' => ['lat' => 30.9010, 'lng' => 75.8573],
        'amritsar' => ['lat' => 31.6340, 'lng' => 74.8711],
        'kanpur' => ['lat' => 26.4499, 'lng' => 80.3319],
        'varanasi' => ['lat' => 25.3176, 'lng' => 82.9789],
        'patna' => ['lat' => 25.5941, 'lng' => 85.1376],
        'ranchi' => ['lat' => 23.3441, 'lng' => 85.3096],
        'asansol' => ['lat' => 23.6850, 'lng' => 86.9649],
        'dhanbad' => ['lat' => 23.7957, 'lng' => 86.4304],
        'bhubaneswar' => ['lat' => 20.2961, 'lng' => 85.8245],
        'raipur' => ['lat' => 21.2514, 'lng' => 81.6296],
        'jabalpur' => ['lat' => 23.1815, 'lng' => 79.9864],
        'vadodara' => ['lat' => 22.3072, 'lng' => 73.1812],
        'rajkot' => ['lat' => 22.3039, 'lng' => 70.8022],
        'thiruvananthapuram' => ['lat' => 8.5241, 'lng' => 76.9366],
        'kozhikode' => ['lat' => 11.2588, 'lng' => 75.7804],
        'thrissur' => ['lat' => 10.5276, 'lng' => 76.2144],
        'alappuzha' => ['lat' => 9.4881, 'lng' => 76.3388],
        'madurai' => ['lat' => 9.9252, 'lng' => 78.1198],
        'coimbatore' => ['lat' => 11.0066, 'lng' => 76.9569],
        'salem' => ['lat' => 11.6643, 'lng' => 78.1460],
        'tiruchirappalli' => ['lat' => 10.7905, 'lng' => 78.7047],
        'mysuru' => ['lat' => 12.2958, 'lng' => 76.6394],
        'hubballi' => ['lat' => 15.3647, 'lng' => 75.1240],
        'davanagere' => ['lat' => 14.4644, 'lng' => 75.9228],
        'vijayawada' => ['lat' => 16.5062, 'lng' => 80.6480],
        'guntur' => ['lat' => 16.3867, 'lng' => 80.4277],
        'nellore' => ['lat' => 14.4426, 'lng' => 79.9864],
    ];
    
    $nameLower = strtolower(trim($name));
    error_log('Looking up lowercased name: ' . $nameLower);
    
    if (isset($cityCoordinates[$nameLower])) {
        error_log('Found city in map: ' . $nameLower);
        return $cityCoordinates[$nameLower];
    }
    
    // If city not in map, try partial match
    foreach ($cityCoordinates as $city => $coords) {
        if (stripos($nameLower, substr($city, 0, 3)) === 0) {
            error_log('Found partial match: ' . $city);
            return $coords;
        }
    }
    
    // Last resort: try to find a railway station in that city and compute average coords
    try {
        $sql = "SELECT AVG(latitude) as avg_lat, AVG(longitude) as avg_lng FROM railway_stations WHERE LOWER(city) = LOWER(?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && $result['avg_lat'] && $result['avg_lng']) {
            error_log('Found city in railway_stations: ' . $name);
            return ['lat' => (float)$result['avg_lat'], 'lng' => (float)$result['avg_lng']];
        }
    } catch (Exception $e) {
        error_log('Error querying railway_stations: ' . $e->getMessage());
    }
    
    error_log('City not found in map or database: ' . $nameLower);
    return null;
}

function geocodeViaNominatim($query) {
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
    $normalizedQuery = strtolower(trim($query));
    if (isset($cityAliases[$normalizedQuery])) {
        $query = $cityAliases[$normalizedQuery];
    }
    
    $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($query . ', India');
    if (!function_exists('curl_init')) {
        return null;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'RouteIQ/1.0 (your-email@example.com)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLOPT_HTTP_CODE);
    curl_close($ch);
    if (!$resp || $httpCode !== 200) return null;
    $json = json_decode($resp, true);
    if (!is_array($json) || empty($json)) return null;
    $first = $json[0];
    if (isset($first['lat'], $first['lon'])) {
        return ['lat' => (float)$first['lat'], 'lng' => (float)$first['lon']];
    }
    return null;
}

function cleanCityName($cityName) {
    return trim(preg_replace('/\s+/', ' ', (string)$cityName));
}
?>

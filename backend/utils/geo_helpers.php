<?php
/**
 * Geographic Helper Functions
 * Distance calculations and polyline generation for multi-modal routing
 */

/**
 * Calculate distance between two lat/lng points using Haversine formula
 * @param float $lat1 Latitude of point 1
 * @param float $lng1 Longitude of point 1
 * @param float $lat2 Latitude of point 2
 * @param float $lng2 Longitude of point 2
 * @return float Distance in kilometers
 */
function haversine_distance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371; // km
    
    $dlat = deg2rad($lat2 - $lat1);
    $dlng = deg2rad($lng2 - $lng1);
    
    $a = sin($dlat/2) * sin($dlat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dlng/2) * sin($dlng/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earth_radius * $c;
}

/**
 * Create a straight polyline between two points
 * Used for rail segments (conceptual routing)
 * @param array $from ['lat' => float, 'lng' => float]
 * @param array $to ['lat' => float, 'lng' => float]
 * @return array Polyline array with from and to points
 */
function create_straight_polyline($from, $to) {
    return [
        ['lat' => $from['lat'], 'lng' => $from['lng']],
        ['lat' => $to['lat'], 'lng' => $to['lng']]
    ];
}

/**
 * Interpolate points along a great circle arc (for flight paths)
 * @param array $from ['lat' => float, 'lng' => float]
 * @param array $to ['lat' => float, 'lng' => float]
 * @param int $num_points Number of intermediate points (default 20)
 * @return array Polyline array with interpolated points
 */
function interpolate_great_circle($from, $to, $num_points = 20) {
    $points = [];
    
    $lat1 = deg2rad($from['lat']);
    $lng1 = deg2rad($from['lng']);
    $lat2 = deg2rad($to['lat']);
    $lng2 = deg2rad($to['lng']);
    
    $d = 2 * asin(sqrt(
        pow(sin(($lat1 - $lat2) / 2), 2) +
        cos($lat1) * cos($lat2) * pow(sin(($lng1 - $lng2) / 2), 2)
    ));
    
    for ($i = 0; $i <= $num_points; $i++) {
        $f = $i / $num_points;
        
        $a = sin((1 - $f) * $d) / sin($d);
        $b = sin($f * $d) / sin($d);
        
        $x = $a * cos($lat1) * cos($lng1) + $b * cos($lat2) * cos($lng2);
        $y = $a * cos($lat1) * sin($lng1) + $b * cos($lat2) * sin($lng2);
        $z = $a * sin($lat1) + $b * sin($lat2);
        
        $lat = atan2($z, sqrt($x * $x + $y * $y));
        $lng = atan2($y, $x);
        
        $points[] = [
            'lat' => rad2deg($lat),
            'lng' => rad2deg($lng)
        ];
    }
    
    return $points;
}

/**
 * Find nearest station to given coordinates
 * @param mysqli $conn Database connection
 * @param float $lat Latitude
 * @param float $lng Longitude
 * @return array|null Station data or null if not found
 */
function find_nearest_station($conn, $lat, $lng) {
    $lat = floatval($lat);
    $lng = floatval($lng);
    
    // Use Haversine formula in SQL for accurate distance calculation
    $sql = "SELECT 
                id, name, code, city, state, latitude, longitude, importance,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance_km
            FROM railway_stations
            WHERE importance IN ('major', 'junction')
            ORDER BY distance_km ASC
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ddd', $lat, $lng, $lat);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'code' => $row['code'],
            'city' => $row['city'],
            'state' => $row['state'],
            'lat' => floatval($row['latitude']),
            'lng' => floatval($row['longitude']),
            'distance_km' => round(floatval($row['distance_km']), 2)
        ];
    }
    
    return null;
}

/**
 * Find nearest airport to given coordinates
 * @param mysqli $conn Database connection
 * @param float $lat Latitude
 * @param float $lng Longitude
 * @return array|null Airport data or null if not found
 */
function find_nearest_airport($conn, $lat, $lng) {
    $lat = floatval($lat);
    $lng = floatval($lng);
    
    // Use Haversine formula in SQL
    $sql = "SELECT 
                id, name, iata_code, city, state, latitude, longitude, is_international,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance_km
            FROM airports
            ORDER BY distance_km ASC
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ddd', $lat, $lng, $lat);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'iata_code' => $row['iata_code'],
            'city' => $row['city'],
            'state' => $row['state'],
            'lat' => floatval($row['latitude']),
            'lng' => floatval($row['longitude']),
            'is_international' => (bool)$row['is_international'],
            'distance_km' => round(floatval($row['distance_km']), 2)
        ];
    }
    
    return null;
}

/**
 * Get road segment between two points using OSRM with fallback
 * @param array $from ['lat' => float, 'lng' => float]
 * @param array $to ['lat' => float, 'lng' => float]
 * @return array ['polyline' => [], 'distance_km' => float, 'duration_min' => int]
 */
function get_road_segment($from, $to) {
    $url = "http://router.project-osrm.org/route/v1/driving/{$from['lng']},{$from['lat']};{$to['lng']},{$to['lat']}?overview=full&geometries=geojson";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60, // Increased timeout for long routes / demo server
        CURLOPT_USERAGENT => 'RouteIQ/2.0',
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // If OSRM fails, use straight-line distance estimate
    if ($httpCode !== 200 || !$response) {
        $distance = haversine_distance($from['lat'], $from['lng'], $to['lat'], $to['lng']);
        $duration = round($distance / 100 * 60); // Assume ~100 km/h average
        
        // Create simple 2-point polyline
        return [
            'polyline' => [
                ['lat' => $from['lat'], 'lng' => $from['lng']],
                ['lat' => $to['lat'], 'lng' => $to['lng']]
            ],
            'distance_km' => round($distance, 2),
            'duration_min' => $duration
        ];
    }
    
    $data = json_decode($response, true);
    if (!isset($data['routes'][0])) {
        // Fallback to straight line if OSRM returns no routes
        $distance = haversine_distance($from['lat'], $from['lng'], $to['lat'], $to['lng']);
        $duration = round($distance / 100 * 60);
        
        return [
            'polyline' => [
                ['lat' => $from['lat'], 'lng' => $from['lng']],
                ['lat' => $to['lat'], 'lng' => $to['lng']]
            ],
            'distance_km' => round($distance, 2),
            'duration_min' => $duration
        ];
    }
    
    $route = $data['routes'][0];
    $polyline = [];
    
    if (isset($route['geometry']['coordinates'])) {
        foreach ($route['geometry']['coordinates'] as $c) {
            $polyline[] = ['lat' => $c[1], 'lng' => $c[0]];
        }
    }
    
    return [
        'polyline' => $polyline,
        'distance_km' => round($route['distance'] / 1000, 2),
        'duration_min' => round($route['duration'] / 60)
    ];
}

/**
 * Geocode a place name using Nominatim
 * @param string $place Place name
 * @return array|null ['lat' => float, 'lng' => float, 'display_name' => string] or null
 */
function geocode_place($place) {
    global $conn; // Ensure we have DB access (assumes called where $conn exists or need to import it)
    if (!isset($conn)) $conn = require __DIR__ . '/../db.php';

    $normalized = strtolower(trim($place));
    $hash = md5($normalized);

    // 1. CHECK CACHE
    $stmt = $conn->prepare("SELECT lat, lng, display_name FROM cache_geocoding WHERE query_hash = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            return [
                'lat' => floatval($row['lat']),
                'lng' => floatval($row['lng']),
                'display_name' => $row['display_name']
            ];
        }
        $stmt->close();
    }

    // 2. EXTERNAL REQUEST
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($place) . "&limit=1";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'RouteIQ/2.0 (Multi-Modal Routing)',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        error_log("Geocoding failed for '$place': HTTP $httpCode, $curlError");
        return null;
    }
    
    $data = json_decode($response, true);
    if (!$data || empty($data)) {
        error_log("Geocoding returned no results for '$place'");
        return null;
    }
    
    $result = [
        'lat' => floatval($data[0]['lat']),
        'lng' => floatval($data[0]['lon']),
        'display_name' => $data[0]['display_name'] ?? $place
    ];

    // 3. CACHE RESULT
    $stmt = $conn->prepare("INSERT IGNORE INTO cache_geocoding (query_hash, query_text, lat, lng, display_name) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('ssdds', $hash, $normalized, $result['lat'], $result['lng'], $result['display_name']);
        $stmt->execute();
        $stmt->close();
    }
    
    return $result;
}

/**
 * Helper: read JSON body
 */
function getJsonInput() {
    $raw = file_get_contents('php://input');
    return $raw ? json_decode($raw, true) : [];
}

/**
 * Helper: respond as JSON
 */
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
?>

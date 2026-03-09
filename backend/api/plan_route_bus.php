<?php
/**
 * plan_route_bus.php
 * Bus-specific route planning
 * 
 * POST /api/plan-route-bus
 * Body: {
 *   source: "city_name",
 *   destination: "city_name",
 *   source_coords?: {lat, lng},
 *   destination_coords?: {lat, lng}
 * }
 * 
 * RESPONSE (Phase 3 Journey Contract):
 * {
 *   ok: true,
 *   mode: "bus",
 *   source: "Mumbai",
 *   destination: "Pune",
 *   total_distance_km: 150,
 *   total_duration_min: 180,
 *   cost: 750,
 *   source_coords: {lat, lng},
 *   destination_coords: {lat, lng},
 *   segments: [
 *     {type: "road", label: "Bus Journey", from: "Mumbai", to: "Pune", distance_km, duration_min, polyline}
 *   ]
 * }
 */

require_once __DIR__ . '/../public/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

// Bus routing constants
const BUS_SPEED_KMH = 65;           // Average bus speed on highways (includes stops)
const ROAD_DISTANCE_MULTIPLIER = 1.25;  // Road distance factor (1.25x straight line distance)
const BUS_COST_PER_KM = 5;          // Cost per km (₹5/km typical for intercity buses)

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
    
    $source = trim($input['source'] ?? '');
    $destination = trim($input['destination'] ?? '');
    
    if (!$source || !$destination) {
        throw new Exception('Missing source or destination');
    }

    if (strtolower($source) === strtolower($destination)) {
        throw new Exception('Source and destination cannot be the same');
    }

    // Try to get coordinates from input, otherwise use hardcoded map
    $sourceCoords = $input['source_coords'] ?? null;
    $destCoords = $input['destination_coords'] ?? null;

    if (!$sourceCoords || !isset($sourceCoords['lat']) || !isset($sourceCoords['lng'])) {
        $sourceCoords = getCityCoordinates($source);
    }
    
    if (!$destCoords || !isset($destCoords['lat']) || !isset($destCoords['lng'])) {
        $destCoords = getCityCoordinates($destination);
    }

    if (!$sourceCoords || !$destCoords) {
        $missing = [];
        if (!$sourceCoords) $missing[] = "source: '$source'";
        if (!$destCoords) $missing[] = "destination: '$destination'";
        throw new Exception('Unable to resolve coordinates for: ' . implode(', ', $missing));
    }

    // Plan the bus route
    $result = planBusRoute($sourceCoords, $destCoords, $source, $destination);
    
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

function planBusRoute(array $sourceCoords, array $destCoords, $sourceName, $destName) {
    // Calculate straight-line distance
    $straightLineDistance = calculateHaversineDistance(
        $sourceCoords['lat'],
        $sourceCoords['lng'],
        $destCoords['lat'],
        $destCoords['lng']
    );
    
    // Apply road distance multiplier (buses travel on roads, not straight lines)
    $roadDistance = $straightLineDistance * ROAD_DISTANCE_MULTIPLIER;
    
    // Calculate duration based on bus speed
    $durationMin = round(($roadDistance / BUS_SPEED_KMH) * 60);
    
    // Calculate cost
    $cost = round($roadDistance * BUS_COST_PER_KM);
    
    // Build single road segment
    $segments = [
        [
            'type' => 'road',
            'label' => 'Bus Journey',
            'from' => $sourceName,
            'to' => $destName,
            'from_name' => 'Source',
            'to_name' => 'Destination',
            'distance_km' => round($roadDistance, 2),
            'duration_min' => $durationMin,
            'polyline' => generateRoadPolyline(
                $sourceCoords['lat'],
                $sourceCoords['lng'],
                $destCoords['lat'],
                $destCoords['lng']
            )
        ]
    ];
    
    return [
        'ok' => true,
        'mode' => 'bus',
        'source' => $sourceName,
        'destination' => $destName,
        'total_distance_km' => round($roadDistance, 2),
        'total_duration_min' => $durationMin,
        'cost' => $cost,
        'source_coords' => ['lat' => $sourceCoords['lat'], 'lng' => $sourceCoords['lng']],
        'destination_coords' => ['lat' => $destCoords['lat'], 'lng' => $destCoords['lng']],
        'segments' => $segments,
        'polyline' => $segments[0]['polyline']
    ];
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Calculate Haversine distance between two coordinates in kilometers
 */
function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadiusKm = 6371;
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadiusKm * $c;
}

/**
 * Get city coordinates from hardcoded map
 */
function getCityCoordinates($cityName) {
    // City aliases for common Indian city names
    $cityAliases = [
        'vizag' => 'Visakhapatnam',
        'vizagpatnam' => 'Visakhapatnam',
        'kolkata' => 'Kolkata',
        'calcutta' => 'Kolkata',
        'bangalore' => 'Bangalore',
        'bengaluru' => 'Bangalore',
        'bombay' => 'Mumbai',
        'pune' => 'Pune',
        'puna' => 'Pune',
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
        'cochin' => 'Kochi',
        'thiruvananthapuram' => 'Thiruvananthapuram',
        'trivandrum' => 'Thiruvananthapuram',
        'kottayam' => 'Kottayam',
        'thrissur' => 'Thrissur',
        'kozhikode' => 'Kozhikode',
        'calicut' => 'Kozhikode',
        'kannur' => 'Kannur',
        'kasaragod' => 'Kasaragod',
        'hyderabad' => 'Hyderabad',
        'secunderabad' => 'Hyderabad',
        'visakhapatnam' => 'Visakhapatnam',
        'vijayawada' => 'Vijayawada',
        'goa' => 'Goa',
        'panaji' => 'Goa',
        'srinagar' => 'Srinagar',
        'delhi' => 'Delhi',
        'new delhi' => 'Delhi',
        'mumbai' => 'Mumbai',
        'bangalore' => 'Bangalore',
        'bengaluru' => 'Bangalore',
        'hyderabad' => 'Hyderabad',
        'chennai' => 'Chennai',
        'madras' => 'Chennai'
    ];
    
    // Apply alias mapping
    $normalizedCityName = strtolower(trim($cityName));
    if (isset($cityAliases[$normalizedCityName])) {
        $cityName = $cityAliases[$normalizedCityName];
    }
    
    // First, try the database cache
    try {
        $db = get_db_connection();
        $queryHash = md5(strtolower($cityName));
        $stmt = $db->prepare("SELECT lat, lng FROM cache_geocoding WHERE query_hash = ?");
        $stmt->execute([$queryHash]);
        $cached = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cached) {
            return ['lat' => floatval($cached['lat']), 'lng' => floatval($cached['lng'])];
        }
    } catch (Exception $e) {
        // Fall through to hardcoded map
    }
    
    // Fallback to hardcoded city map
    $cityMap = [
        'Mumbai' => ['lat' => 19.0760, 'lng' => 72.8777],
        'Pune' => ['lat' => 18.5204, 'lng' => 73.8567],
        'Bangalore' => ['lat' => 12.9716, 'lng' => 77.5946],
        'Hyderabad' => ['lat' => 17.3850, 'lng' => 78.4867],
        'Chennai' => ['lat' => 13.0827, 'lng' => 80.2707],
        'Kolkata' => ['lat' => 22.5726, 'lng' => 88.3639],
        'Delhi' => ['lat' => 28.7041, 'lng' => 77.1025],
        'Jaipur' => ['lat' => 26.9124, 'lng' => 75.7873],
        'Ahmedabad' => ['lat' => 23.0225, 'lng' => 72.5714],
        'Vijayawada' => ['lat' => 16.5062, 'lng' => 80.6480],
        'Indore' => ['lat' => 22.7196, 'lng' => 75.8577],
        'Lucknow' => ['lat' => 26.8467, 'lng' => 80.9462],
        'Goa' => ['lat' => 15.2993, 'lng' => 73.8243],
        'Kochi' => ['lat' => 9.9312, 'lng' => 76.2673],
        'Chandigarh' => ['lat' => 30.7333, 'lng' => 76.7794],
        'Srinagar' => ['lat' => 34.0837, 'lng' => 74.7973],
        'Visakhapatnam' => ['lat' => 17.6869, 'lng' => 83.2185],
        'Bhopal' => ['lat' => 23.1815, 'lng' => 79.9864],
        'Nagpur' => ['lat' => 21.1458, 'lng' => 79.0882],
        'Coimbatore' => ['lat' => 11.0026, 'lng' => 76.7055]
    ];
    
    // Case-insensitive lookup
    foreach ($cityMap as $city => $coords) {
        if (strtolower($city) === strtolower($cityName)) {
            return $coords;
        }
    }
    
    return null;
}

/**
 * Generate a polyline between two coordinates
 * Returns array of lat/lng points
 */
function generateRoadPolyline($lat1, $lon1, $lat2, $lon2) {
    $points = [];
    
    // Start point
    $points[] = ['lat' => $lat1, 'lng' => $lon1];
    
    // Intermediate points (for visual representation)
    // Add points along the line for better visual effect
    $steps = 10;
    for ($i = 1; $i < $steps; $i++) {
        $ratio = $i / $steps;
        $points[] = [
            'lat' => $lat1 + ($lat2 - $lat1) * $ratio,
            'lng' => $lon1 + ($lon2 - $lon1) * $ratio
        ];
    }
    
    // End point
    $points[] = ['lat' => $lat2, 'lng' => $lon2];
    
    return $points;
}

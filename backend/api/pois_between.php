<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';

function getPOIsBetweenCoordinates($lat1, $lon1, $lat2, $lon2) {
    $db = get_db_connection();
    
    // Get bounding box for the route (with 2km buffer on each side)
    $minLat = min($lat1, $lat2) - 0.05;
    $maxLat = max($lat1, $lat2) + 0.05;
    $minLon = min($lon1, $lon2) - 0.05;
    $maxLon = max($lon1, $lon2) + 0.05;
    
    // Get temples and restaurants between the two cities
    $query = "SELECT 
        id,
        name,
        category,
        latitude,
        longitude,
        SQRT(POWER(latitude - ?, 2) + POWER(longitude - ?, 2)) as distance_from_start
    FROM pois
    WHERE category IN ('temple', 'restaurant')
    AND latitude BETWEEN ? AND ?
    AND longitude BETWEEN ? AND ?
    ORDER BY distance_from_start ASC
    LIMIT 20";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$lat1, $lon1, $minLat, $maxLat, $minLon, $maxLon]);
    
    $pois = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pois[] = $row;
    }
    
    return $pois;
}

// Get parameters from request
$lat1 = isset($_GET['lat1']) ? floatval($_GET['lat1']) : null;
$lon1 = isset($_GET['lon1']) ? floatval($_GET['lon1']) : null;
$lat2 = isset($_GET['lat2']) ? floatval($_GET['lat2']) : null;
$lon2 = isset($_GET['lon2']) ? floatval($_GET['lon2']) : null;

if (!$lat1 || !$lon1 || !$lat2 || !$lon2) {
    echo json_encode(['error' => 'Missing coordinates: lat1, lon1, lat2, lon2 required']);
    exit;
}

try {
    $pois = getPOIsBetweenCoordinates($lat1, $lon1, $lat2, $lon2);
    
    // Separate by category
    $temples = array_filter($pois, fn($p) => $p['category'] === 'temple');
    $restaurants = array_filter($pois, fn($p) => $p['category'] === 'restaurant');
    
    echo json_encode([
        'success' => true,
        'temples' => array_values($temples),
        'restaurants' => array_values($restaurants),
        'total' => count($pois),
        'temple_count' => count($temples),
        'restaurant_count' => count($restaurants)
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>

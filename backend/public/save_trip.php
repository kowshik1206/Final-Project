<?php
/**
 * save_trip.php - Save a trip to database
 * POST /save_trip.php
 * Body: { title, from_lat, from_lng, to_lat, to_lng, distance_m, duration_s, geometry_geojson }
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    json_response(['ok' => false, 'error' => 'Invalid JSON'], 400);
}

$title = $input['title'] ?? 'Untitled Trip';
$fromLat = (float)($input['from_lat'] ?? 0);
$fromLng = (float)($input['from_lng'] ?? 0);
$toLat = (float)($input['to_lat'] ?? 0);
$toLng = (float)($input['to_lng'] ?? 0);
$distanceM = (int)($input['distance_m'] ?? 0);
$durationS = (int)($input['duration_s'] ?? 0);
$geometry = $input['geometry_geojson'] ?? null;

// Validate coordinates
if (!validate_coordinates($fromLat, $fromLng) || !validate_coordinates($toLat, $toLng)) {
    json_response(['ok' => false, 'error' => 'Invalid coordinates'], 400);
}

if (!$geometry) {
    json_response(['ok' => false, 'error' => 'Missing geometry'], 400);
}

// Store geometry as JSON string
$geometryJson = json_encode($geometry);

try {
    $db = get_db_connection();
    
    $stmt = $db->prepare("
        INSERT INTO trips 
        (title, origin_lat, origin_lng, dest_lat, dest_lng, distance_m, duration_s, geometry_geojson, created_at) 
        VALUES 
        (:title, :origin_lat, :origin_lng, :dest_lat, :dest_lng, :distance_m, :duration_s, :geometry, NOW())
    ");
    
    $stmt->execute([
        ':title' => $title,
        ':origin_lat' => $fromLat,
        ':origin_lng' => $fromLng,
        ':dest_lat' => $toLat,
        ':dest_lng' => $toLng,
        ':distance_m' => $distanceM,
        ':duration_s' => $durationS,
        ':geometry' => $geometryJson,
    ]);
    
    $tripId = $db->lastInsertId();
    
    json_response([
        'ok' => true,
        'trip_id' => (int)$tripId,
        'message' => 'Trip saved successfully',
    ], 201);
    
} catch (Exception $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}

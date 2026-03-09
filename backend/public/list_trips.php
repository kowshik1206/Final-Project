<?php
/**
 * list_trips.php - List public trips
 * GET /list_trips.php?limit=10&offset=0
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once 'db.php';

$limit = (int)($_GET['limit'] ?? 20);
$offset = (int)($_GET['offset'] ?? 0);

// Validate input
if ($limit > 100) $limit = 100;
if ($limit < 1) $limit = 10;
if ($offset < 0) $offset = 0;

try {
    $db = get_db_connection();
    
    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM trips");
    $countStmt->execute();
    $countResult = $countStmt->fetch();
    $total = $countResult['total'] ?? 0;
    
    // Get trips (recently created first)
    $stmt = $db->prepare("
        SELECT id, title, source_name, dest_name, selected_mode,
               origin_lat, origin_lng, dest_lat, dest_lng, 
               distance_km, duration_min, cost_amount, created_at 
        FROM trips 
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $trips = $stmt->fetchAll();
    
    json_response([
        'ok' => true,
        'trips' => $trips,
        'pagination' => [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total,
        ]
    ], 200);
    
} catch (Exception $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}

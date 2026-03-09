<?php
/**
 * api/trips.php — Trips management endpoint
 * GET /api/trips - List trips
 * POST /api/trips - Save a trip
 * DELETE /api/trips - Delete a trip
 */

require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];

// Handle GET - List trips
if ($method === 'GET') {
    try {
        $db = get_db_connection();
        
        // Check if we're getting a specific trip
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT * FROM trips WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $trip = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$trip) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Trip not found']);
                exit;
            }
            
            // Parse JSON fields for display
            if (isset($trip['vehicle_json']) && is_string($trip['vehicle_json'])) {
                $trip['vehicle_json'] = json_decode($trip['vehicle_json'], true) ?: [];
            }
            if (isset($trip['stops_json']) && is_string($trip['stops_json'])) {
                $trip['stops_json'] = json_decode($trip['stops_json'], true) ?: [];
            }
            if (isset($trip['segments_json']) && is_string($trip['segments_json'])) {
                $trip['segments_json'] = json_decode($trip['segments_json'], true) ?: [];
            }
            
            // Build costs object for compatibility with TripDetailsPage
            $costs = [
                'total' => (float)($trip['cost_amount'] ?? 0),
                'fuel_cost' => (float)($trip['cost_amount'] ?? 0),
                'car' => (float)($trip['cost_amount'] ?? 0)
            ];
            $trip['costs_json'] = json_encode($costs);
            
            http_response_code(200);
            echo json_encode(['ok' => true, 'trip' => $trip]);
        } else {
            // List all trips with pagination
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            $stmt = $db->prepare("SELECT * FROM trips ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
            $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode(['ok' => true, 'data' => $trips]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle POST - Save a trip
if ($method === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
            exit;
        }
        
        $db = get_db_connection();
        
        // Extract fields from input
        $source = $input['source'] ?? '';
        $destination = $input['destination'] ?? '';
        $selectedMode = $input['selected_mode'] ?? 'car';
        $distanceKm = (float)($input['distance_km'] ?? 0);
        $durationMin = (int)($input['duration_min'] ?? 0);
        $cost = (float)($input['cost'] ?? 0);
        $sourceCoords = $input['source_coords'] ?? ['lat' => 0, 'lng' => 0];
        $destCoords = $input['destination_coords'] ?? ['lat' => 0, 'lng' => 0];
        $polyline = $input['polyline'] ?? [];
        $stopsJson = $input['stops_json'] ?? '[]';
        $vehicleJson = $input['vehicle_json'] ?? '{}';
        $segments = $input['segments'] ?? [];
        
        // Build title
        $title = $input['title'] ?? "{$source} → {$destination}";
        
        // Convert coords to strings if they're arrays
        $originLat = is_array($sourceCoords) ? ($sourceCoords['lat'] ?? 0) : $sourceCoords;
        $originLng = is_array($sourceCoords) ? ($sourceCoords['lng'] ?? 0) : 0;
        $destLat = is_array($destCoords) ? ($destCoords['lat'] ?? 0) : $destCoords;
        $destLng = is_array($destCoords) ? ($destCoords['lng'] ?? 0) : 0;
        
        // Insert trip
        $stmt = $db->prepare("
            INSERT INTO trips 
            (title, source_name, dest_name, selected_mode, distance_km, duration_min, cost_amount, 
             origin_lat, origin_lng, dest_lat, dest_lng, stops_json, vehicle_json, segments_json, created_at, costs_json) 
            VALUES 
            (:title, :source, :destination, :mode, :distance, :duration, :cost, 
             :orig_lat, :orig_lng, :dest_lat, :dest_lng, :stops, :vehicle, :segments, NOW(), :costs)
        ");
        
        // Prepare costs_json - include mode, total, and fuel_cost
        $costsJson = json_encode([
            'total' => $cost,
            'fuel_cost' => $cost,
            'mode' => $selectedMode
        ]);
        
        $stmt->execute([
            ':title' => $title,
            ':source' => $source,
            ':destination' => $destination,
            ':mode' => $selectedMode,
            ':distance' => $distanceKm,
            ':duration' => $durationMin,
            ':cost' => $cost,
            ':orig_lat' => $originLat,
            ':orig_lng' => $originLng,
            ':dest_lat' => $destLat,
            ':dest_lng' => $destLng,
            ':stops' => is_string($stopsJson) ? $stopsJson : json_encode($stopsJson),
            ':vehicle' => is_string($vehicleJson) ? $vehicleJson : json_encode($vehicleJson),
            ':segments' => json_encode($segments),
            ':costs' => $costsJson
        ]);
        
        // Store polyline separately if the table supports it
        $tripId = $db->lastInsertId();
        if (!empty($polyline)) {
            try {
                $updateStmt = $db->prepare("UPDATE trips SET geometry_geojson = ? WHERE id = ?");
                $updateStmt->execute([json_encode($polyline), $tripId]);
            } catch (Exception $e) {
                // Table might not have geometry_geojson column, that's OK
            }
        }
        
        http_response_code(201);
        echo json_encode([
            'ok' => true,
            'trip_id' => (int)$tripId,
            'message' => 'Trip saved successfully',
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle DELETE - Delete a trip
if ($method === 'DELETE') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Trip ID required']);
            exit;
        }
        
        $db = get_db_connection();
        
        $stmt = $db->prepare("DELETE FROM trips WHERE id = ?");
        $stmt->execute([$input['id']]);
        
        http_response_code(200);
        echo json_encode(['ok' => true, 'message' => 'Trip deleted successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);

?>

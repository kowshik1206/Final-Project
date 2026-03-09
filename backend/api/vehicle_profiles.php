<?php
header('Content-Type: application/json; charset=utf-8');

$conn = require_once __DIR__ . '/../db.php';

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database connection failed']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$query_string = $_SERVER['QUERY_STRING'] ?? '';
parse_str($query_string, $query_params);

// GET /api/vehicle-profiles or GET /api/vehicle-profiles?id=1
if ($method === 'GET') {
    $profile_id = isset($query_params['id']) ? intval($query_params['id']) : null;
    
    if ($profile_id) {
        $stmt = $conn->prepare("SELECT id, name, fuel, efficiency_km_per_unit, unit FROM vehicle_profiles WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $profile_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();
        $stmt->close();
        
        if (!$profile) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Profile not found']);
            exit;
        }
        
        echo json_encode(['ok' => true, 'data' => format_profile($profile)]);
    } else {
        $result = $conn->query("SELECT id, name, fuel, efficiency_km_per_unit, unit FROM vehicle_profiles ORDER BY id ASC");
        $profiles = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        $formatted = array_map('format_profile', $profiles);
        echo json_encode(['ok' => true, 'data' => $formatted]);
    }
}

// POST /api/vehicle-profiles - Create new profile
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!validate_profile_data($data)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid profile data']);
        exit;
    }
    
    $stmt = $conn->prepare("
        INSERT INTO vehicle_profiles (name, fuel, efficiency_km_per_unit, unit)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        "ssds",
        $data['name'],
        $data['fuel'],
        $data['efficiency_km_per_unit'],
        $data['unit']
    );
    
    if ($stmt->execute()) {
        $profile_id = $conn->insert_id;
        $stmt->close();
        
        // Fetch the created profile
        $stmt = $conn->prepare("SELECT id, name, fuel, efficiency_km_per_unit, unit FROM vehicle_profiles WHERE id = ?");
        $stmt->bind_param("i", $profile_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();
        $stmt->close();
        
        http_response_code(201);
        echo json_encode(['ok' => true, 'data' => format_profile($profile)]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Failed to create profile']);
    }
}

// PUT /api/vehicle-profiles?id=1 - Update profile
elseif ($method === 'PUT') {
    $profile_id = isset($query_params['id']) ? intval($query_params['id']) : null;
    
    if (!$profile_id) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Profile ID required']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!validate_profile_data($data, false)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid profile data']);
        exit;
    }
    
    $stmt = $conn->prepare("
        UPDATE vehicle_profiles
        SET name = ?, fuel = ?, efficiency_km_per_unit = ?, unit = ?
        WHERE id = ?
    ");
    
    $stmt->bind_param(
        "ssdsi",
        $data['name'],
        $data['fuel'],
        $data['efficiency_km_per_unit'],
        $data['unit'],
        $profile_id
    );
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows === 0) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Profile not found']);
            exit;
        }
        
        $stmt->close();
        
        // Fetch updated profile
        $stmt = $conn->prepare("SELECT id, name, fuel, efficiency_km_per_unit, unit FROM vehicle_profiles WHERE id = ?");
        $stmt->bind_param("i", $profile_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();
        $stmt->close();
        
        echo json_encode(['ok' => true, 'data' => format_profile($profile)]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Failed to update profile']);
    }
}

// DELETE /api/vehicle-profiles?id=1 - Delete profile
elseif ($method === 'DELETE') {
    $profile_id = isset($query_params['id']) ? intval($query_params['id']) : null;
    
    if (!$profile_id) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Profile ID required']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM vehicle_profiles WHERE id = ?");
    $stmt->bind_param("i", $profile_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows === 0) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Profile not found']);
            exit;
        }
        
        $stmt->close();
        echo json_encode(['ok' => true, 'message' => 'Profile deleted']);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Failed to delete profile']);
    }
} else {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
}

// Helper function to format profile with typed numbers
function format_profile($profile) {
    return [
        'id' => (int)$profile['id'],
        'name' => (string)$profile['name'],
        'fuel' => (string)$profile['fuel'],
        'efficiency_km_per_unit' => (float)$profile['efficiency_km_per_unit'],
        'unit' => (string)$profile['unit']
    ];
}

// Helper function to validate profile data
function validate_profile_data($data, $require_all = true) {
    if (!is_array($data)) {
        return false;
    }
    
    if ($require_all) {
        return isset($data['name']) && !empty($data['name']) &&
               isset($data['fuel']) && in_array($data['fuel'], ['petrol', 'diesel', 'cng']) &&
               isset($data['efficiency_km_per_unit']) && is_numeric($data['efficiency_km_per_unit']) && $data['efficiency_km_per_unit'] > 0 &&
               isset($data['unit']) && in_array($data['unit'], ['L', 'kg']);
    } else {
        // Partial update - all fields optional but must be valid if present
        return (!isset($data['name']) || !empty($data['name'])) &&
               (!isset($data['fuel']) || in_array($data['fuel'], ['petrol', 'diesel', 'cng'])) &&
               (!isset($data['efficiency_km_per_unit']) || (is_numeric($data['efficiency_km_per_unit']) && $data['efficiency_km_per_unit'] > 0)) &&
               (!isset($data['unit']) || in_array($data['unit'], ['L', 'kg']));
    }
}
?>

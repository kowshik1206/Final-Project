<?php
// backend/api/health.php
// Phase 4: Reliability - Observability Endpoint

header('Content-Type: application/json');

try {
    $conn = require __DIR__ . '/../db.php';
    
    // Check DB
    if ($conn->ping()) {
        $dbStatus = 'ok';
    } else {
        throw new Exception('DB ping failed');
    }

    // Check OSRM (Optional - just checking if reachable? Skip for speed unless critical)
    // We'll just report DB for now as System Health.
    
    echo json_encode([
        'status' => 'ok',
        'database' => 'connected',
        'timestamp' => time()
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>

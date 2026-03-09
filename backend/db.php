<?php
/**
 * backend/db.php
 * Returns a mysqli connection ($conn) for use with require_once.
 * Edit DB credentials below to match your environment.
 */

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'routeiq');
define('DB_PORT', 3306);

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Check connection
if ($conn->connect_error) {
    // If required directly, output JSON (dev). In usual use, we just return connection.
    header('Content-Type: application/json', true, 500);
    echo json_encode(['ok' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Set charset
$conn->set_charset("utf8mb4");

// Return the mysqli connection
return $conn;
?>

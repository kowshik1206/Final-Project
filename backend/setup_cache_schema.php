<?php
$conn = require_once __DIR__ . '/db.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS cache_geocoding (
        query_hash VARCHAR(64) PRIMARY KEY,
        query_text VARCHAR(255),
        lat DECIMAL(10, 6) NOT NULL,
        lng DECIMAL(10, 6) NOT NULL,
        display_name VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS cache_routes (
        route_hash VARCHAR(64) PRIMARY KEY,
        source_coords VARCHAR(50),
        dest_coords VARCHAR(50),
        mode VARCHAR(20),
        json_data MEDIUMTEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    // Optimization: Add index to trips for faster listing
    "ALTER TABLE trips ADD INDEX IF NOT EXISTS idx_created_at (created_at)"
];

foreach ($queries as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Success: " . substr($sql, 0, 50) . "...\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}
echo "Cache schema setup complete.\n";
?>

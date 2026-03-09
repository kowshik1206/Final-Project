<?php
$conn = require_once __DIR__ . '/db.php';

$sql = "CREATE TABLE IF NOT EXISTS rate_limits (
    ip VARCHAR(45) PRIMARY KEY,
    request_count INT DEFAULT 0,
    window_start INT UNSIGNED NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Rate limit table created.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
?>

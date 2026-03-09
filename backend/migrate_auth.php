<?php
// backend/migrate_auth.php
$conn = require_once __DIR__ . '/db.php';

echo "Migrating Auth Tables...\n";

// 1. Create Users Table
$sqlUsers = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sqlUsers)) {
    echo "[OK] Users table checked/created.\n";
} else {
    echo "[ERR] Failed to create users table: " . $conn->error . "\n";
}

// 2. Add user_id to Trips
// Check if column exists first
$checkCol = $conn->query("SHOW COLUMNS FROM trips LIKE 'user_id'");
if ($checkCol->num_rows == 0) {
    $sqlAlter = "ALTER TABLE trips ADD COLUMN user_id INT NULL AFTER id";
    if ($conn->query($sqlAlter)) {
        echo "[OK] Added user_id to trips table.\n";
    } else {
        echo "[ERR] Failed to add user_id to trips: " . $conn->error . "\n";
    }
} else {
    echo "[OK] user_id column already exists in trips.\n";
}

echo "Migration Complete.\n";
?>

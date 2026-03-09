<?php
// backend/public/test_db_connection.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $conn = require_once '../db.php';
    if ($conn instanceof mysqli) {
        if ($conn->connect_error) {
           echo "JSON_ERROR: " . $conn->connect_error;
        } else {
           echo "Database connection successful!";
        }
    } else {
        echo "Failed to include db.php or it did not return a connection.";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}

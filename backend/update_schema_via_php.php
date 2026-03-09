<?php
require_once __DIR__ . '/db.php';

$p1 = "ALTER TABLE trips ADD COLUMN IF NOT EXISTS vehicle_json JSON";
$p2 = "ALTER TABLE trips ADD COLUMN IF NOT EXISTS stops_json JSON";

if ($conn->query($p1) === TRUE) {
    echo "Added vehicle_json column.\n";
} else {
    echo "Error adding vehicle_json: " . $conn->error . "\n";
}

if ($conn->query($p2) === TRUE) {
    echo "Added stops_json column.\n";
} else {
    echo "Error adding stops_json: " . $conn->error . "\n";
}
?>

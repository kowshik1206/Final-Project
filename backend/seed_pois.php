<?php
require_once __DIR__ . '/db.php';

// $conn is set by db.php
if (!isset($conn)) {
   global $conn;
}

echo "Seeding POIs...\n";

// Coordinates for Mumbai-Pune Expressway area
// 1. Food Mall near Khalapur
$p1 = [
    'name' => 'Food Mall Khalapur',
    'lat' => 18.8297,
    'lon' => 73.3275,
    'cat' => 'restaurant',
    'brand' => 'McDonalds',
    'source' => 'manual',
    'tags' => json_encode(['amenity' => 'food_court', 'cuisine' => 'fast_food'])
];

// 2. Petrol Pump near Lonavala
$p2 = [
    'name' => 'HP Petrol Pump - Lonavala',
    'lat' => 18.7545,
    'lon' => 73.4050,
    'cat' => 'fuel',
    'brand' => 'Hindustan Petroleum',
    'source' => 'manual',
    'tags' => json_encode(['fuel:diesel' => 'yes', 'fuel:petrol' => 'yes'])
];

$pois = [$p1, $p2];

foreach ($pois as $p) {
    // Check if exists
    $check = $conn->prepare("SELECT id FROM pois WHERE name = ? AND latitude = ?");
    $check->bind_param("ss", $p['name'], $p['lat']);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo "Skipping {$p['name']} (already exists)\n";
        continue;
    }

    $stmt = $conn->prepare("INSERT INTO pois (name, latitude, longitude, category, brand, source, tags, confidence) VALUES (?, ?, ?, ?, ?, ?, ?, 1.0)");
    $stmt->bind_param("sddssss", $p['name'], $p['lat'], $p['lon'], $p['cat'], $p['brand'], $p['source'], $p['tags']);
    
    if ($stmt->execute()) {
        echo "Inserted {$p['name']}\n";
    } else {
        echo "Error: " . $stmt->error . "\n";
    }
}

echo "Done.\n";

<?php
/**
 * Setup script to create and seed vehicle_profiles table
 * Run: php backend/setup_vehicles.php
 */

$conn = require_once __DIR__ . '/db.php';

if (!$conn || $conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "🔧 Setting up vehicle_profiles table...\n";

// Create table
$create_sql = "
CREATE TABLE IF NOT EXISTS vehicle_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    fuel ENUM('petrol', 'diesel', 'cng', 'electric') NOT NULL,
    efficiency_km_per_unit DECIMAL(5, 2) NOT NULL,
    unit ENUM('L', 'kg', 'kWh') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
";

if ($conn->query($create_sql)) {
    echo "✅ Table created successfully\n";
} else {
    echo "⚠️  Table may already exist: " . $conn->error . "\n";
}

// Check if table is empty
$result = $conn->query("SELECT COUNT(*) as count FROM vehicle_profiles");
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    echo "ℹ️  Table already has " . $row['count'] . " vehicles\n";
} else {
    echo "📦 Seeding vehicle data...\n";
    
    $vehicles = [
        // Petrol
        ['Maruti Swift', 'petrol', 18.5, 'L'],
        ['Hyundai i20', 'petrol', 17.2, 'L'],
        ['Tata Nexon', 'petrol', 16.8, 'L'],
        // Diesel
        ['Toyota Innova', 'diesel', 12.5, 'L'],
        ['Mahindra XUV500', 'diesel', 14.2, 'L'],
        ['Ford Endeavour', 'diesel', 11.8, 'L'],
        // CNG
        ['Maruti Alto CNG', 'cng', 22.5, 'kg'],
        ['Tata Tiago CNG', 'cng', 21.0, 'kg'],
        ['Hyundai Santro CNG', 'cng', 20.5, 'kg'],
        // Electric
        ['Tesla Model 3', 'electric', 6.0, 'kWh'],
        ['Tata Nexon EV', 'electric', 5.2, 'kWh'],
        ['MG ZS EV', 'electric', 5.5, 'kWh'],
    ];
    
    $insert_sql = "INSERT INTO vehicle_profiles (name, fuel, efficiency_km_per_unit, unit) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    foreach ($vehicles as $vehicle) {
        $stmt->bind_param("ssds", $vehicle[0], $vehicle[1], $vehicle[2], $vehicle[3]);
        if (!$stmt->execute()) {
            echo "❌ Error inserting " . $vehicle[0] . ": " . $stmt->error . "\n";
        }
    }
    
    $stmt->close();
    echo "✅ Seeded " . count($vehicles) . " vehicles\n";
}

// Show all vehicles
echo "\n📋 Current vehicles:\n";
$result = $conn->query("SELECT id, name, fuel, efficiency_km_per_unit, unit FROM vehicle_profiles ORDER BY fuel, name");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  [{$row['id']}] {$row['name']} ({$row['fuel']}) - {$row['efficiency_km_per_unit']} {$row['unit']}\n";
    }
}

$conn->close();
echo "\n✅ Setup complete!\n";
?>

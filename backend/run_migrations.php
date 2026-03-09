<?php
/**
 * Migration runner script
 * Executes SQL migration files in order
 */

require_once __DIR__ . '/db.php';

$migrations = [
    'database/migrations/001_create_railway_stations.sql',
    'database/migrations/002_create_airports.sql',
    'database/migrations/003_seed_railway_stations.sql',
    'database/migrations/004_seed_airports.sql',
];

echo "Running migrations for RouteIQ...\n\n";

foreach ($migrations as $file) {
    $filepath = __DIR__ . '/' . $file;
    
    if (!file_exists($filepath)) {
        echo "❌ SKIP: $file (file not found)\n";
        continue;
    }
    
    echo "📦 Running: $file\n";
    
    $sql = file_get_contents($filepath);
    
    // Execute using multi_query
    if ($conn->multi_query($sql)) {
        do {
            // Flush results
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        
        echo "   ✅ Done\n\n";
    } else {
        echo "   ⚠️  Error: " . $conn->error . "\n\n";
    }
}

// Verify results
echo "Verification:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM railway_stations");
$row = $result->fetch_assoc();
echo "  Railway stations: " . $row['count'] . "\n";

$result = $conn->query("SELECT COUNT(*) as count FROM airports");
$row = $result->fetch_assoc();
echo "  Airports: " . $row['count'] . "\n";

echo "\n✅ Migration complete!\n";

$conn->close();
?>

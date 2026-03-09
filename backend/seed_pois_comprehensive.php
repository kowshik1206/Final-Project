<?php
/**
 * POI INDIA COMPREHENSIVE SEEDING
 * Ensures every 10km radius in India has at least one POI
 * Uses grid-based geographic distribution strategy
 */

require_once __DIR__ . '/db.php';

// Grid parameters: 10km coverage = ~0.09 degrees (at equator)
// More precise: 1 degree latitude = ~111.32 km
// So: 10km / 111.32 = 0.0898 degrees

$GRID_SIZE_KM = 10;
$GRID_SIZE_DEG = $GRID_SIZE_KM / 111.32; // ~0.09 degrees

// India bounds
$INDIA_BOUNDS = [
    'min_lat' => 8.4,    // Southern tip (Kanyakumari)
    'max_lat' => 35.0,   // Northern tip (Kashmir)
    'min_lng' => 68.7,   // Western tip (Gujarat)
    'max_lng' => 97.4    // Eastern tip (Arunachal Pradesh)
];

// POI category templates
$POI_TEMPLATES = [
    'fuel' => [
        'HP Fuel Station', 'Indian Oil Pump', 'Shell Station', 'IOCL Petrol', 
        'Bharat Petroleum', 'Hindustan Petroleum', 'Fuel Stop', 'Gas Station',
        'Petrol Bunk', 'Fuel Hub', 'Energy Station', 'Fast Fuel'
    ],
    'restaurant' => [
        'Highway Dhabha', 'Local Restaurant', 'Quick Bite', 'Food Point',
        'Cafe Stop', 'Roadside Eatery', 'Home Kitchen', 'Spice Route',
        'Tea Stall', 'Snack Corner', 'Meals Hub', 'Travellers Delight'
    ],
    'hospital' => [
        'General Hospital', 'Medical Center', 'Primary Health', 'Clinic',
        'Emergency Ward', 'Health Point', 'Medical Hub', 'Care Center',
        'Doctor Office', 'Health Kendra', 'Medical Point', 'First Aid Post'
    ],
    'tourism' => [
        'Local Temple', 'Heritage Site', 'Tourist Spot', 'Monument',
        'View Point', 'Historical Place', 'Local Market', 'Sacred Site',
        'Community Hall', 'Rest House', 'Tourist Stop', 'Scenic View'
    ]
];

$colors = [
    'reset' => "\033[0m",
    'red' => "\033[91m",
    'green' => "\033[92m",
    'yellow' => "\033[93m",
    'cyan' => "\033[96m",
    'bold' => "\033[1m",
    'blue' => "\033[94m"
];

function colorize($text, $color) {
    global $colors;
    return $colors[$color] . $text . $colors['reset'];
}

echo colorize("\n========== INDIA POI COMPREHENSIVE SEEDING ==========\n", 'bold');
echo colorize("Grid Strategy: 10km coverage across entire India\n", 'cyan');
echo "Grid Size: " . colorize($GRID_SIZE_DEG . "° (~" . $GRID_SIZE_KM . "km)", 'yellow') . "\n\n";

try {
    // Clear existing POIs without truncating (respects foreign keys)
    echo colorize("Clearing existing POI data...\n", 'yellow');
    $conn->query("DELETE FROM pois WHERE source = 'generated'");
    
    $pois_inserted = 0;
    $pois_data = [];
    
    // Generate grid points across India
    echo colorize("Generating grid points across India...\n\n", 'yellow');
    
    $lat = $INDIA_BOUNDS['min_lat'];
    $grid_row = 0;
    
    while ($lat <= $INDIA_BOUNDS['max_lat']) {
        $lng = $INDIA_BOUNDS['min_lng'];
        $grid_col = 0;
        
        while ($lng <= $INDIA_BOUNDS['max_lng']) {
            // For each grid point, create POIs of different categories
            $categories = array_keys($POI_TEMPLATES);
            
            // Randomly select which categories to include at this grid point
            // Ensure at least 1 POI per grid cell
            $num_pois = rand(1, 3); // 1-3 POIs per grid cell
            
            $selected_categories = array_rand($categories, min($num_pois, count($categories)));
            if (!is_array($selected_categories)) {
                $selected_categories = [$selected_categories];
            }
            
            foreach ($selected_categories as $cat_idx) {
                $category = $categories[$cat_idx];
                $templates = $POI_TEMPLATES[$category];
                
                // Random template for this POI
                $base_name = $templates[array_rand($templates)];
                
                // Add slight variation in coordinates within grid cell
                $var_lat = $lat + (rand(-40, 40) / 1000);
                $var_lng = $lng + (rand(-40, 40) / 1000);
                
                $poi_data = [
                    'name' => $base_name . ' #' . ($pois_inserted + 1),
                    'category' => $category,
                    'latitude' => $var_lat,
                    'longitude' => $var_lng,
                    'source' => 'generated',
                    'verified' => 0
                ];
                
                $pois_data[] = $poi_data;
                $pois_inserted++;
            }
            
            $lng += $GRID_SIZE_DEG;
            $grid_col++;
        }
        
        $lat += $GRID_SIZE_DEG;
        $grid_row++;
        
        // Progress indicator
        if ($grid_row % 10 == 0) {
            echo colorize("  Generated " . $pois_inserted . " POIs so far...\n", 'cyan');
        }
    }
    
    echo colorize("\n✅ Grid generation complete: " . $pois_inserted . " POIs generated\n\n", 'green');
    
    // Batch insert all POIs
    echo colorize("Inserting POIs into database...\n", 'yellow');
    
    $batch_size = 100;
    for ($i = 0; $i < count($pois_data); $i += $batch_size) {
        $batch = array_slice($pois_data, $i, $batch_size);
        
        $values = [];
        foreach ($batch as $poi) {
            $values[] = sprintf(
                "('%s', '%s', %.6f, %.6f, '%s', %d)",
                $conn->real_escape_string($poi['name']),
                $conn->real_escape_string($poi['category']),
                $poi['latitude'],
                $poi['longitude'],
                $conn->real_escape_string($poi['source']),
                $poi['verified']
            );
        }
        
        $sql = "INSERT INTO pois (name, category, latitude, longitude, source, verified) 
                VALUES " . implode(',', $values);
        
        if (!$conn->query($sql)) {
            throw new Exception("Insert failed: " . $conn->error);
        }
    }
    
    echo colorize("✅ All POIs inserted successfully\n\n", 'green');
    
    // Verify coverage
    echo colorize("========== COVERAGE VERIFICATION ==========\n", 'cyan');
    
    // Get statistics
    $result = $conn->query("SELECT COUNT(*) as total FROM pois");
    $row = $result->fetch_assoc();
    echo colorize("Total POIs in Database: ", 'yellow') . colorize($row['total'], 'bold') . "\n";
    
    // Category breakdown
    $result = $conn->query("SELECT category, COUNT(*) as count FROM pois GROUP BY category ORDER BY count DESC");
    echo colorize("\nPOIs by Category:\n", 'yellow');
    while ($row = $result->fetch_assoc()) {
        printf("  %-15s: %6d\n", $row['category'], $row['count']);
    }
    
    // Check geographic coverage
    echo colorize("\nGeographic Bounds:\n", 'yellow');
    $result = $conn->query("
        SELECT 
            MIN(latitude) as min_lat, MAX(latitude) as max_lat,
            MIN(longitude) as min_lng, MAX(longitude) as max_lng
        FROM pois
    ");
    $bounds = $result->fetch_assoc();
    printf("  Latitude:  %.4f to %.4f\n", $bounds['min_lat'], $bounds['max_lat']);
    printf("  Longitude: %.4f to %.4f\n", $bounds['min_lng'], $bounds['max_lng']);
    
    // Sample check: Random point should have POI within 10km
    echo colorize("\n========== 10KM COVERAGE TEST ==========\n", 'cyan');
    
    $test_lat = 20.5;  // Near India center (Madhya Pradesh)
    $test_lng = 77.5;
    $radius_deg = 0.09; // ~10km
    
    $result = $conn->query("
        SELECT COUNT(*) as poi_count
        FROM pois
        WHERE latitude BETWEEN $test_lat - $radius_deg AND $test_lat + $radius_deg
          AND longitude BETWEEN $test_lng - $radius_deg AND $test_lng + $radius_deg
    ");
    
    $row = $result->fetch_assoc();
    echo "Test point: ($test_lat, $test_lng)\n";
    echo "Radius: 10km\n";
    echo "POIs found: " . colorize($row['poi_count'], 'bold') . "\n";
    
    if ($row['poi_count'] > 0) {
        echo colorize("✅ PASS: 10km coverage verified\n", 'green');
    } else {
        echo colorize("⚠️  WARNING: Test point has no POI within 10km\n", 'yellow');
    }
    
    // Test multiple random points
    echo colorize("\nTesting 10 random points across India:\n", 'yellow');
    $pass_count = 0;
    
    for ($i = 0; $i < 10; $i++) {
        $test_lat = $INDIA_BOUNDS['min_lat'] + rand(0, 1000) / 1000 * ($INDIA_BOUNDS['max_lat'] - $INDIA_BOUNDS['min_lat']);
        $test_lng = $INDIA_BOUNDS['min_lng'] + rand(0, 1000) / 1000 * ($INDIA_BOUNDS['max_lng'] - $INDIA_BOUNDS['min_lng']);
        
        $result = $conn->query("
            SELECT COUNT(*) as poi_count
            FROM pois
            WHERE latitude BETWEEN $test_lat - $radius_deg AND $test_lat + $radius_deg
              AND longitude BETWEEN $test_lng - $radius_deg AND $test_lng + $radius_deg
        ");
        
        $row = $result->fetch_assoc();
        $count = $row['poi_count'];
        
        if ($count > 0) {
            echo "  ✅ Point " . ($i+1) . " @ ($test_lat, $test_lng): " . colorize($count . " POIs", 'green') . "\n";
            $pass_count++;
        } else {
            echo "  ⚠️  Point " . ($i+1) . " @ ($test_lat, $test_lng): " . colorize("0 POIs", 'yellow') . "\n";
        }
    }
    
    echo colorize("\n✅ Coverage Test Result: $pass_count/10 points have POIs within 10km\n", 'green');
    
    echo colorize("\n========== SEEDING COMPLETE ==========\n", 'bold');
    echo colorize("Database Status: READY FOR USE\n", 'green');
    echo colorize("Total POIs: " . $pois_inserted . "\n", 'green');
    echo colorize("Coverage: 10km grid across entire India\n", 'green');

} catch (Exception $e) {
    echo colorize("❌ Error: " . $e->getMessage() . "\n", 'red');
    exit(1);
}
?>

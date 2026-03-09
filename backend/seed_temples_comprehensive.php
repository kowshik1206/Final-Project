<?php
/**
 * seed_temples_comprehensive.php
 * Seed 5000+ temples across India with geographic distribution
 * Covers all states with temples organized by region
 */

require_once __DIR__ . '/public/db.php';

$db = get_db_connection();

// Define temple templates for different regions with varying names
$templeNames = [
    // Generic temple names that can be adapted
    'Shiva Temple', 'Vishnu Temple', 'Devi Temple', 'Hanuman Temple', 'Krishna Temple',
    'Ram Mandir', 'Durga Temple', 'Kali Temple', 'Saraswati Temple', 'Lakshmi Temple',
    'Ganesha Temple', 'Murugan Temple', 'Surya Temple', 'Chandi Temple', 'Bhairav Temple',
    'Narayan Temple', 'Govind Temple', 'Radha Krishna Temple', 'Mahadev Temple', 'Mahakali Temple',
    'Nandi Temple', 'Veerabhadra Temple', 'Annapurna Temple', 'Renuka Temple', 'Bhawani Temple',
    'Bhadrakali Temple', 'Yellamma Temple', 'Basaveshwara Temple', 'Veerabhadra Temple', 'Maidavaram Temple'
];

// Indian states with approximate boundaries and major cities
$stateRegions = [
    // Format: [state, lat_center, lng_center, lat_range, lng_range, num_temples]
    ['Uttar Pradesh', 27.0, 78.0, 5, 6, 450],
    ['Maharashtra', 19.5, 76.0, 4, 5, 420],
    ['Karnataka', 15.5, 76.0, 4, 5, 400],
    ['Tamil Nadu', 11.5, 79.0, 4, 4, 380],
    ['Andhra Pradesh', 15.5, 79.0, 4, 4, 380],
    ['Telangana', 17.5, 78.5, 2, 2, 280],
    ['Gujarat', 22.0, 72.0, 4, 4, 350],
    ['Rajasthan', 27.0, 74.0, 6, 6, 380],
    ['West Bengal', 24.5, 88.0, 4, 5, 300],
    ['Bihar', 25.5, 85.5, 3, 3, 250],
    ['Madhya Pradesh', 22.5, 78.0, 4, 4, 350],
    ['Jharkhand', 23.5, 84.0, 3, 3, 200],
    ['Chhattisgarh', 21.5, 82.0, 3, 3, 250],
    ['Odisha', 20.0, 85.0, 2, 2, 200],
    ['Punjab', 31.5, 75.0, 3, 2, 200],
    ['Himachal Pradesh', 32.0, 77.0, 3, 2, 180],
    ['Uttarakhand', 30.5, 79.0, 3, 2, 200],
    ['Haryana', 29.5, 77.5, 1, 1, 120],
    ['Delhi', 28.6, 77.2, 0.5, 0.5, 100],
    ['Jammu & Kashmir', 34.0, 75.5, 4, 4, 180],
    ['Goa', 15.5, 73.8, 1, 1, 100],
    ['Kerala', 10.5, 76.5, 3, 2, 250],
    ['Assam', 26.0, 92.0, 4, 4, 200],
    ['Meghalaya', 25.0, 91.5, 1, 1, 80],
    ['Mizoram', 23.5, 93.0, 1, 1, 60],
    ['Nagaland', 26.0, 94.0, 1, 1, 60],
    ['Manipur', 24.5, 94.0, 1, 1, 60],
    ['Tripura', 23.5, 91.5, 1, 1, 50],
    ['Arunachal Pradesh', 28.0, 93.0, 2, 2, 80],
];

echo "🕉️  Starting comprehensive temple seeding (5000+ temples)...\n";
echo "Target: 5000+ temples across all of India\n\n";

$inserted = 0;
$skipped = 0;
$totalTarget = 0;

foreach ($stateRegions as $region) {
    [$state, $lat_center, $lng_center, $lat_range, $lng_range, $num_temples] = $region;
    $totalTarget += $num_temples;
    
    echo "📍 Processing $state ($num_temples temples)...\n";
    
    for ($i = 0; $i < $num_temples; $i++) {
        try {
            // Generate random coordinates within state boundaries
            $lat = $lat_center + (rand(-$lat_range * 100, $lat_range * 100) / 100);
            $lng = $lng_center + (rand(-$lng_range * 100, $lng_range * 100) / 100);
            
            // Clamp to valid India coordinates
            $lat = max(8.0, min(37.0, $lat));
            $lng = max(68.0, min(97.5, $lng));
            
            // Generate temple name
            $baseName = $templeNames[array_rand($templeNames)];
            $suffix = ['Temple', 'Mandir', 'Shrine', 'Dham', 'Sthal'][array_rand(['Temple', 'Mandir', 'Shrine', 'Dham', 'Sthal'])];
            $templeName = $baseName . ' - ' . $state . ' #' . ($i + 1);
            
            // Check if temple already exists (by name and very close coordinates)
            $checkStmt = $db->prepare(
                "SELECT id FROM pois WHERE category = 'temple' AND ABS(latitude - ?) < 0.01 AND ABS(longitude - ?) < 0.01 LIMIT 1"
            );
            $checkStmt->execute([$lat, $lng]);
            $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($checkResult) {
                $skipped++;
                continue;
            }
            
            // Insert temple
            $stmt = $db->prepare(
                "INSERT INTO pois (name, category, latitude, longitude) VALUES (?, ?, ?, ?)"
            );
            
            if ($stmt->execute([$templeName, 'temple', $lat, $lng])) {
                $inserted++;
            } else {
                $skipped++;
            }
            
            // Progress indicator
            if (($i + 1) % 50 == 0) {
                echo "  ✓ {$i} temples processed...\n";
            }
            
        } catch (Exception $e) {
            $skipped++;
        }
    }
    
    echo "  ✅ Completed $state\n\n";
}

echo "\n";
echo "====================================\n";
echo "✅ Total Inserted: $inserted temples\n";
echo "⏭️  Total Skipped: $skipped temples\n";
echo "📊 Target: $totalTarget temples\n";
echo "====================================\n\n";

// Verify final count
$countResult = $db->query("SELECT COUNT(*) as total FROM pois WHERE category = 'temple'");
$countRow = $countResult->fetch(PDO::FETCH_ASSOC);
$totalTemples = $countRow['total'];

echo "📈 Final Statistics:\n";
echo "  • Total temples in database: $totalTemples\n";

// Show distribution
echo "\n🗺️  Temple distribution by state:\n";
$stateStats = [];
foreach ($stateRegions as $region) {
    [$state, $lat_center, $lng_center, $lat_range, $lng_range, $num_temples] = $region;
    $stateStats[] = ['state' => $state, 'target' => $num_temples];
}

// Display summary
$topStates = array_slice($stateStats, 0, 10);
foreach ($topStates as $stat) {
    echo "  • {$stat['state']}: {$stat['target']} temples\n";
}

echo "\n✨ Temple seeding completed!\n";
echo "🎯 Total temples added: " . ($totalTemples > 5000 ? "✅ 5000+ ACHIEVED!" : "$totalTemples (Target: 5000+)") . "\n";
?>

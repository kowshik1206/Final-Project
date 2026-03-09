<?php
/**
 * POI DUPLICATE CHECKER - Check all POIs across India for duplicates
 * Analyzes duplicate patterns, original vs duplicate records
 * Shows: Total records, duplicates found, statistics
 */

require_once __DIR__ . '/db.php';

// Color codes for CLI output
$colors = [
    'reset' => "\033[0m",
    'red' => "\033[91m",
    'green' => "\033[92m",
    'yellow' => "\033[93m",
    'blue' => "\033[94m",
    'cyan' => "\033[96m",
    'bold' => "\033[1m"
];

function colorize($text, $color) {
    global $colors;
    return $colors[$color] . $text . $colors['reset'];
}

echo colorize("\n========== POI DUPLICATE ANALYSIS - ALL INDIA ==========\n", 'bold');
echo colorize("Checking for duplicate POIs across the database...\n\n", 'cyan');

try {
    // 1. TOTAL RECORDS COUNT
    $result = $conn->query("SELECT COUNT(*) as total FROM pois");
    $row = $result->fetch_assoc();
    $totalRecords = $row['total'];
    echo colorize("📊 Total POI Records: ", 'yellow') . colorize($totalRecords, 'bold') . "\n\n";

    // 2. CATEGORY BREAKDOWN
    echo colorize("📋 Records by Category:\n", 'yellow');
    $result = $conn->query("
        SELECT category, COUNT(*) as count 
        FROM pois 
        GROUP BY category 
        ORDER BY count DESC
    ");
    while ($row = $result->fetch_assoc()) {
        printf("   %-20s: %6d\n", $row['category'], $row['count']);
    }

    // 3. DUPLICATE DETECTION BY NAME + LAT/LNG (Exact duplicates)
    echo colorize("\n\n🔍 EXACT DUPLICATES (Same name, latitude, longitude):\n", 'yellow');
    $result = $conn->query("
        SELECT 
            name, 
            latitude, 
            longitude, 
            category,
            COUNT(*) as duplicate_count,
            GROUP_CONCAT(id) as ids
        FROM pois 
        GROUP BY ROUND(latitude, 5), ROUND(longitude, 5), name
        HAVING COUNT(*) > 1
        ORDER BY duplicate_count DESC
    ");

    $exactDuplicates = [];
    $exactDuplicateCount = 0;
    while ($row = $result->fetch_assoc()) {
        $exactDuplicates[] = $row;
        $exactDuplicateCount += $row['duplicate_count'] - 1;
        echo colorize("  ❌ ", 'red') . "{$row['name']}\n";
        echo "     📍 Lat: {$row['latitude']}, Lng: {$row['longitude']}\n";
        echo "     🏷️  Category: {$row['category']}\n";
        echo colorize("     📌 Found {$row['duplicate_count']} times (IDs: {$row['ids']})\n", 'red');
    }

    if (count($exactDuplicates) === 0) {
        echo colorize("   ✅ No exact duplicates found!\n", 'green');
    } else {
        echo colorize("\n   Total Exact Duplicates: " . count($exactDuplicates) . " (affecting " . $exactDuplicateCount . " records)\n", 'red');
    }

    // 4. NEAR DUPLICATES (Same name, similar location - within 100m)
    echo colorize("\n\n🔎 NEAR DUPLICATES (Same name, within 100m):\n", 'yellow');
    $result = $conn->query("
        SELECT 
            p1.id as id1,
            p2.id as id2,
            p1.name,
            p1.category,
            p1.latitude, p1.longitude,
            p2.latitude, p2.longitude,
            ROUND(SQRT(POWER((p1.latitude - p2.latitude) * 111.32, 2) + 
                      POWER((p1.longitude - p2.longitude) * 111.32 * COS(RADIANS(p1.latitude)), 2)), 2) as distance_m
        FROM pois p1
        JOIN pois p2 ON 
            p1.name = p2.name AND 
            p1.id < p2.id AND
            ABS(p1.latitude - p2.latitude) < 0.01 AND
            ABS(p1.longitude - p2.longitude) < 0.01
        HAVING distance_m < 100
        ORDER BY distance_m ASC
    ");

    $nearDuplicates = [];
    $nearDuplicateCount = 0;
    while ($row = $result->fetch_assoc()) {
        $nearDuplicates[] = $row;
        $nearDuplicateCount++;
        echo colorize("  ⚠️  ", 'yellow') . "{$row['name']}\n";
        echo "     📍 ID {$row['id1']}: ({$row['latitude']}, {$row['longitude']})\n";
        echo "     📍 ID {$row['id2']}: ({$row['latitude']}, {$row['longitude']})\n";
        echo "     📏 Distance: " . colorize($row['distance_m'] . "m", 'yellow') . "\n";
    }

    if (count($nearDuplicates) === 0) {
        echo colorize("   ✅ No near duplicates found!\n", 'green');
    } else {
        echo colorize("\n   Total Near Duplicates: " . $nearDuplicateCount . " pairs\n", 'yellow');
    }

    // 5. SOURCE ANALYSIS (If available)
    echo colorize("\n\n📡 POI Sources:\n", 'yellow');
    $result = $conn->query("
        SELECT source, COUNT(*) as count 
        FROM pois 
        WHERE source IS NOT NULL
        GROUP BY source 
        ORDER BY count DESC
    ");
    $hasSourceData = false;
    while ($row = $result->fetch_assoc()) {
        $hasSourceData = true;
        printf("   %-20s: %6d\n", $row['source'], $row['count']);
    }
    if (!$hasSourceData) {
        echo "   ℹ️  Source data not available\n";
    }

    // 6. VERIFICATION STATUS
    echo colorize("\n\n✔️  Verification Status:\n", 'yellow');
    $result = $conn->query("
        SELECT 
            verified, 
            COUNT(*) as count 
        FROM pois 
        GROUP BY verified
    ");
    while ($row = $result->fetch_assoc()) {
        $status = $row['verified'] ? 'Verified' : 'Unverified';
        printf("   %-15s: %6d\n", $status, $row['count']);
    }

    // 7. SUMMARY STATISTICS
    echo colorize("\n\n📈 SUMMARY STATISTICS:\n", 'yellow');
    echo "   Total Records: " . colorize($totalRecords, 'bold') . "\n";
    echo "   Exact Duplicates: " . ($exactDuplicateCount > 0 ? colorize($exactDuplicateCount . " records", 'red') : colorize("0 (✅ OK)", 'green')) . "\n";
    echo "   Near Duplicates: " . ($nearDuplicateCount > 0 ? colorize($nearDuplicateCount . " pairs", 'yellow') : colorize("0 (✅ OK)", 'green')) . "\n";
    
    $duplicatePercentage = ($totalRecords > 0) ? round(($exactDuplicateCount / $totalRecords) * 100, 2) : 0;
    echo "   Duplicate Rate: " . ($duplicatePercentage > 0 ? colorize($duplicatePercentage . "%", 'red') : colorize("0% (✅ OK)", 'green')) . "\n";

    // 8. DATA QUALITY ANALYSIS
    echo colorize("\n\n🎯 DATA QUALITY ANALYSIS:\n", 'yellow');
    
    $result = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN name IS NULL OR name = '' THEN 1 ELSE 0 END) as missing_name,
            SUM(CASE WHEN latitude IS NULL THEN 1 ELSE 0 END) as missing_lat,
            SUM(CASE WHEN longitude IS NULL THEN 1 ELSE 0 END) as missing_lng,
            SUM(CASE WHEN category IS NULL OR category = '' THEN 1 ELSE 0 END) as missing_category
        FROM pois
    ");
    
    $quality = $result->fetch_assoc();
    $totalQuality = $quality['total'];
    
    echo "   Missing Name: " . ($quality['missing_name'] > 0 ? colorize($quality['missing_name'], 'red') : colorize("0", 'green')) . "\n";
    echo "   Missing Latitude: " . ($quality['missing_lat'] > 0 ? colorize($quality['missing_lat'], 'red') : colorize("0", 'green')) . "\n";
    echo "   Missing Longitude: " . ($quality['missing_lng'] > 0 ? colorize($quality['missing_lng'], 'red') : colorize("0", 'green')) . "\n";
    echo "   Missing Category: " . ($quality['missing_category'] > 0 ? colorize($quality['missing_category'], 'red') : colorize("0", 'green')) . "\n";

    // 9. SAMPLE DATA (First 10 unique locations)
    echo colorize("\n\n📌 SAMPLE POIs (First 10):\n", 'yellow');
    $result = $conn->query("
        SELECT id, name, category, latitude, longitude, verified 
        FROM pois 
        LIMIT 10
    ");
    $sampleCount = 1;
    while ($row = $result->fetch_assoc()) {
        $verifyBadge = $row['verified'] ? colorize("✅", 'green') : "⭕";
        printf("   %d. %s %s (%s) @ %.4f, %.4f\n", 
            $sampleCount++, 
            $verifyBadge,
            $row['name'], 
            $row['category'], 
            $row['latitude'], 
            $row['longitude']
        );
    }

    // 10. RECOMMENDATIONS
    echo colorize("\n\n💡 RECOMMENDATIONS:\n", 'cyan');
    if ($exactDuplicateCount > 0) {
        echo colorize("   ⚠️  IMMEDIATE ACTION NEEDED:\n", 'red');
        echo "   - Found $exactDuplicateCount exact duplicate records\n";
        echo "   - Run: php backend/cleanup_poi_duplicates.php\n";
        echo "   - Script will merge and deduplicate automatically\n";
    } else {
        echo colorize("   ✅ No exact duplicates - Database is clean!\n", 'green');
    }

    if ($nearDuplicateCount > 0) {
        echo colorize("   ⚠️  MANUAL REVIEW NEEDED:\n", 'yellow');
        echo "   - Found $nearDuplicateCount near-duplicate pairs\n";
        echo "   - Review above list and manually decide if they're duplicates\n";
    } else {
        echo colorize("   ✅ No near duplicates - Good data quality!\n", 'green');
    }

    echo colorize("\n========== END OF REPORT ==========\n", 'bold');

} catch (Exception $e) {
    echo colorize("❌ Error: " . $e->getMessage() . "\n", 'red');
    exit(1);
}
?>

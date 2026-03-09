<?php
/**
 * POI DUPLICATE CLEANUP SCRIPT
 * Automatically removes exact duplicate POI records from database
 * Keeps the record with the earliest creation date
 */

require_once __DIR__ . '/db.php';

// Color codes
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

echo colorize("\n========== POI DUPLICATE CLEANUP ==========\n", 'bold');
echo colorize("Removing exact duplicate POI records...\n\n", 'cyan');

try {
    // Find all exact duplicates
    $result = $conn->query("
        SELECT 
            name, 
            ROUND(latitude, 5) as lat_round,
            ROUND(longitude, 5) as lng_round,
            category,
            GROUP_CONCAT(id ORDER BY id) as ids,
            COUNT(*) as duplicate_count
        FROM pois 
        GROUP BY ROUND(latitude, 5), ROUND(longitude, 5), name
        HAVING COUNT(*) > 1
    ");

    $totalCleaned = 0;
    $duplicateClusters = [];

    while ($row = $result->fetch_assoc()) {
        $ids = explode(',', $row['ids']);
        $keepId = min($ids); // Keep the lowest ID (oldest)
        $deleteIds = array_diff($ids, [$keepId]);

        $duplicateClusters[] = [
            'name' => $row['name'],
            'category' => $row['category'],
            'count' => $row['duplicate_count'],
            'keep_id' => $keepId,
            'delete_ids' => $deleteIds
        ];

        // Delete duplicates
        $deleteIdsList = implode(',', $deleteIds);
        $deleteResult = $conn->query("DELETE FROM pois WHERE id IN ($deleteIdsList)");

        if ($deleteResult) {
            $affected = $conn->affected_rows;
            $totalCleaned += $affected;
            
            echo colorize("✅ CLEANED: ", 'green') . "{$row['name']} ({$row['category']})\n";
            echo "   📍 Location: {$row['lat_round']}, {$row['lng_round']}\n";
            echo "   🔢 IDs deleted: " . colorize(implode(', ', $deleteIds), 'red') . "\n";
            echo "   ✓ Kept ID: " . colorize($keepId, 'green') . "\n\n";
        }
    }

    // Verify cleanup
    echo colorize("\n📊 VERIFICATION:\n", 'yellow');
    $result = $conn->query("SELECT COUNT(*) as total FROM pois");
    $row = $result->fetch_assoc();
    echo "   Total POI Records After Cleanup: " . colorize($row['total'], 'bold') . "\n";

    // Check for remaining duplicates
    $result = $conn->query("
        SELECT COUNT(*) as dup_count FROM (
            SELECT COUNT(*) 
            FROM pois 
            GROUP BY ROUND(latitude, 5), ROUND(longitude, 5), name 
            HAVING COUNT(*) > 1
        ) as dups
    ");
    $row = $result->fetch_assoc();
    $remainingDups = $row['dup_count'];

    if ($remainingDups == 0) {
        echo colorize("   ✅ No remaining exact duplicates!\n", 'green');
    } else {
        echo colorize("   ⚠️  " . $remainingDups . " duplicate clusters remain\n", 'yellow');
    }

    echo colorize("\n📈 SUMMARY:\n", 'yellow');
    echo "   Total Records Deleted: " . colorize($totalCleaned, 'bold') . "\n";
    echo "   Duplicate Clusters Fixed: " . colorize(count($duplicateClusters), 'bold') . "\n";

    echo colorize("\n========== CLEANUP COMPLETE ==========\n", 'green');

} catch (Exception $e) {
    echo colorize("❌ Error: " . $e->getMessage() . "\n", 'red');
    exit(1);
}
?>

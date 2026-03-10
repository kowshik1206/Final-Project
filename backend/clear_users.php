<?php
/**
 * Clear All Users Script
 * 
 * This script deletes all users from the database.
 * Useful for fresh start during development.
 * 
 * WARNING: This will delete ALL user accounts!
 */

require_once __DIR__ . '/db.php';

echo "⚠️  WARNING: This will delete ALL users from the database!\n";
echo "Press Ctrl+C to cancel, or Enter to continue...\n";

if (php_sapi_name() === 'cli') {
    readline();  // Wait for user input in CLI
}

try {
    // Delete all users
    $result = $conn->query("DELETE FROM users");
    
    if ($result) {
        echo "✓ All users deleted successfully.\n";
        
        // Reset auto increment
        $conn->query("ALTER TABLE users AUTO_INCREMENT = 1");
        echo "✓ Auto increment reset.\n";
        
        // Verify
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        $count = $result->fetch_assoc()['count'];
        echo "\nTotal users remaining: $count\n";
        
        echo "\n✓ Database cleared!\n";
        echo "You can now create a new account with proper password_hash.\n";
    } else {
        echo "❌ Error deleting users: " . $conn->error . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>

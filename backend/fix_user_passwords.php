<?php
/**
 * Fix User Password Column Migration
 * 
 * This script fixes the issue where users were created with `password` column
 * instead of `password_hash` column.
 * 
 * Run this once to fix existing users.
 */

require_once __DIR__ . '/db.php';

echo "Starting user password column fix...\n";

try {
    // Check if 'password' column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'password'");
    $passwordColumnExists = $result->num_rows > 0;
    
    // Check if 'password_hash' column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
    $passwordHashColumnExists = $result->num_rows > 0;
    
    if ($passwordColumnExists && $passwordHashColumnExists) {
        echo "Both 'password' and 'password_hash' columns exist.\n";
        echo "Migrating data from 'password' to 'password_hash'...\n";
        
        // Copy data from password to password_hash
        $conn->query("UPDATE users SET password_hash = password WHERE password_hash IS NULL AND password IS NOT NULL");
        echo "Data migrated successfully.\n";
        
        // Drop the old password column
        echo "Dropping old 'password' column...\n";
        $conn->query("ALTER TABLE users DROP COLUMN password");
        echo "Old column dropped.\n";
        
    } elseif ($passwordColumnExists && !$passwordHashColumnExists) {
        echo "Only 'password' column exists. Renaming to 'password_hash'...\n";
        $conn->query("ALTER TABLE users CHANGE COLUMN password password_hash VARCHAR(255)");
        echo "Column renamed successfully.\n";
        
    } elseif (!$passwordColumnExists && $passwordHashColumnExists) {
        echo "✓ Database is correct. 'password_hash' column exists.\n";
        
    } else {
        echo "⚠️ Neither column exists. Creating 'password_hash' column...\n";
        $conn->query("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255)");
        echo "Column created.\n";
    }
    
    // Verify final state
    $result = $conn->query("SHOW COLUMNS FROM users");
    echo "\n✓ Final users table structure:\n";
    echo "-----------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    echo "-----------------------------\n";
    
    // Count users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $count = $result->fetch_assoc()['count'];
    echo "\nTotal users in database: $count\n";
    
    echo "\n✓ Fix completed successfully!\n";
    echo "\nYou can now:\n";
    echo "1. Try logging in with existing credentials\n";
    echo "2. Or create a new account\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>

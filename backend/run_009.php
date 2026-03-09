<?php
require_once __DIR__ . '/db.php';

$sql = file_get_contents(__DIR__ . '/database/migrations/009_analytics_facts.sql');

if ($conn->multi_query($sql)) {
    do {
        // flush result sets
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Migration 009 executed successfully.\n";
} else {
    echo "Error executing migration: " . $conn->error . "\n";
}
?>

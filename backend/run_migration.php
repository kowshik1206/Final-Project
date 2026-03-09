<?php
require_once __DIR__ . '/db.php';

$sql = file_get_contents(__DIR__ . '/pois_extended_test.sql');

if ($conn->multi_query($sql)) {
    do {
        // flush result sets
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Migration `create_pois_india.sql` executed successfully.\n";
} else {
    echo "Error executing migration: " . $conn->error . "\n";
}
?>

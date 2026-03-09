<?php
$conn = require_once __DIR__ . '/db.php';

function desc($table) {
    global $conn;
    echo "TABLE: $table\n";
    $r = $conn->query("DESCRIBE $table");
    while($row = $r->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    echo "\n";
}

desc('pois');
desc('trips');
?>

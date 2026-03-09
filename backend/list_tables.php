<?php
require_once __DIR__ . '/db.php';
$result = $conn->query('SHOW TABLES');
echo "=== DATABASE TABLES ===\n";
while($row = $result->fetch_row()) {
    echo $row[0] . "\n";
}
?>

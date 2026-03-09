<?php
require_once __DIR__ . '/db.php';
$r = $conn->query('SHOW CREATE TABLE trips');
$row = $r->fetch_assoc();
echo "TRIPS:\n" . $row['Create Table'] . "\n\n";

$r2 = $conn->query('SHOW CREATE TABLE pois');
if ($r2) {
    $row2 = $r2->fetch_assoc();
    echo "POIS:\n" . $row2['Create Table'] . "\n";
}
?>

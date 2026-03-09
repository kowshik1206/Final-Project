<?php
require_once __DIR__ . '/db.php';
$res = $conn->query("SELECT COUNT(*) as c FROM pois_india");
$row = $res->fetch_assoc();
echo "Total Records: " . $row['c'] . "\n";
$res = $conn->query("SELECT name, category, route_segment FROM pois_india LIMIT 5");
while($r = $res->fetch_assoc()) {
    echo "- {$r['name']} ({$r['category']}) @ {$r['route_segment']}\n";
}
?>

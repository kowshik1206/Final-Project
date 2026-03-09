<?php
require_once __DIR__ . '/public/db.php';
$db = get_db_connection();
$sql = "SELECT id, code, name, city, latitude, longitude, importance FROM railway_stations WHERE city LIKE '%New%' OR name LIKE '%New%' LIMIT 20";
$stmt = $db->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT);

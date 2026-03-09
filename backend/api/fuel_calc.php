<?php
// backend/api/fuel_calc.php
header('Content-Type: application/json; charset=utf-8');

// load DB if you want to use vehicle_profiles table in future
// $conn = require_once __DIR__ . '/../db.php';

$raw = json_decode(file_get_contents('php://input'), true) ?: [];
$distance_km = isset($raw['distance_km']) ? floatval($raw['distance_km']) : 0.0;
$prices = isset($raw['prices']) && is_array($raw['prices']) ? $raw['prices'] : [];

if ($distance_km <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid or missing distance_km']);
    exit;
}

if (!is_array($prices) || empty($prices)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid or missing prices object']);
    exit;
}

// --- Vehicle profiles (fallback - static). Later you can load from DB.
$profiles = [
    ['id' => 1, 'name' => 'Small Hatchback - Petrol', 'fuel' => 'petrol', 'efficiency' => 18.0, 'unit' => 'L'],
    ['id' => 2, 'name' => 'Sedan - Petrol',           'fuel' => 'petrol', 'efficiency' => 14.0, 'unit' => 'L'],
    ['id' => 3, 'name' => 'SUV - Diesel',             'fuel' => 'diesel', 'efficiency' => 12.0, 'unit' => 'L'],
    ['id' => 4, 'name' => 'Sedan - Diesel',           'fuel' => 'diesel', 'efficiency' => 16.0, 'unit' => 'L'],
    ['id' => 5, 'name' => 'Compact - CNG',            'fuel' => 'cng',    'efficiency' => 26.0, 'unit' => 'kg'],
];

$results = [];

foreach ($profiles as $p) {
    $fuel_type = isset($p['fuel']) ? $p['fuel'] : null;
    $efficiency = isset($p['efficiency']) ? floatval($p['efficiency']) : 0.0;
    if ($efficiency <= 0) continue;

    $price = isset($prices[$fuel_type]) ? floatval($prices[$fuel_type]) : 0.0;

    // consumption (units = L or kg)
    $consumption = round($distance_km / $efficiency, 2);

    // cost
    $cost = round($consumption * $price, 2);

    $results[] = [
        'id' => (int)$p['id'],
        'name' => (string)$p['name'],
        'fuel' => (string)$fuel_type,
        'unit' => (string)$p['unit'],
        'efficiency' => (float)$efficiency,
        'consumption' => (float)$consumption,
        'cost' => (float)$cost
    ];
}

echo json_encode([
    'ok' => true,
    'distance_km' => (float)$distance_km,
    'prices' => $prices,
    'results' => $results
]);

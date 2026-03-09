<?php
// backend/api/cost_calculate.php
header('Content-Type: application/json; charset=utf-8');

$raw = json_decode(file_get_contents("php://input"), true) ?: [];
$distance = floatval($raw['distance_km'] ?? 0);
$passengers = max(1, intval($raw['passengers'] ?? 1));

if ($distance <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid distance_km']);
    exit;
}

// Basic cost model
$car_per_km = 8.0;
$ev_per_km = 3.0;
$train_per_km = 0.5;
$flight_base = 2500.0;
$flight_per_km = 2.0;

// Cost logic: Car/EV is per vehicle, others per person
$vehicle_capacity = 5; // Standard car/suv capacity
$vehicles_needed = ceil($passengers / $vehicle_capacity);

$car_cost = round($distance * $car_per_km * $vehicles_needed, 2);
$ev_cost = round($distance * $ev_per_km * $vehicles_needed, 2);
$train_cost = round($distance * $train_per_km * $passengers, 2);
// Flight: Base fare + distance fare * passengers
$flight_cost = round(($flight_base + ($distance * $flight_per_km)) * $passengers, 2);

$costs = [
    'car' => $car_cost,
    'ev' => $ev_cost,
    'train' => $train_cost,
    'flight' => $flight_cost
];

$best = array_search(min($costs), $costs);

echo json_encode(array_merge(['ok' => true, 'recommendation' => ['mode' => $best, 'reason' => 'cheapest']], $costs));

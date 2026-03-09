<?php
// backend/api/recommend_mode.php
// Authoritative mode recommendation engine
// Pure logic, deterministic scoring

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?: [];

// Extract and validate inputs
$distanceKm = isset($input['distance_km']) ? floatval($input['distance_km']) : 0;
$carCost = isset($input['car_cost']) ? floatval($input['car_cost']) : null;
$evCost = isset($input['ev_cost']) ? floatval($input['ev_cost']) : null;
$evChargingStops = isset($input['ev_charging_stops']) ? intval($input['ev_charging_stops']) : null;
$busCost = isset($input['bus_cost']) ? floatval($input['bus_cost']) : null;
$busDurationMin = isset($input['bus_duration_min']) ? intval($input['bus_duration_min']) : null;
$busStops = isset($input['bus_stops']) ? intval($input['bus_stops']) : null;
$trainCost = isset($input['train_cost']) ? floatval($input['train_cost']) : null;
$trainDurationMin = isset($input['train_duration_min']) ? intval($input['train_duration_min']) : null;
$carDurationMin = isset($input['car_duration_min']) ? intval($input['car_duration_min']) : null;
$passengers = isset($input['passengers']) ? intval($input['passengers']) : 1;
$elders = isset($input['elders']) ? intval($input['elders']) : 0;

// Validation
if ($distanceKm <= 0) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid distance_km: must be > 0'
    ]);
    exit;
}

// Initialize scoring for each mode
$modes = [
    'car' => ['score' => 0, 'reasons' => [], 'feasible' => true],
    'ev' => ['score' => 0, 'reasons' => [], 'feasible' => true],
    'train' => ['score' => 0, 'reasons' => [], 'feasible' => true],
    'bus' => ['score' => 0, 'reasons' => [], 'feasible' => true]
];

// ========== RULE 1: DISTANCE RULES ==========

if ($distanceKm <= 30) {
    // ≤30 km → car preferred
    $modes['car']['score'] += 3;
    $modes['car']['reasons'][] = 'Short distance favors car';
} elseif ($distanceKm <= 250) {
    // 30–250 km → car or ev competitive
    $modes['car']['score'] += 2;
    $modes['car']['reasons'][] = 'Medium distance, car is suitable';
    $modes['ev']['score'] += 2;
    $modes['ev']['reasons'][] = 'Medium distance, EV is viable';
} elseif ($distanceKm <= 600) {
    // 250–600 km → train preferred
    $modes['train']['score'] += 3;
    $modes['train']['reasons'][] = 'Long distance favors train';
    $modes['bus']['score'] += 1;
    $modes['bus']['reasons'][] = 'Long distance, bus available';
} else {
    // >600 km → train strongly preferred
    $modes['train']['score'] += 3;
    $modes['train']['reasons'][] = 'When travel distance exceeds 600 km, trains generally offer the best balance of cost, comfort, and predictability';
}

// ========== RULE 1B: BUS DISTANCE SUITABILITY ==========

if ($busCost !== null) {
    if ($distanceKm >= 50 && $distanceKm <= 300) {
        // 50–300 km → Bus optimal
        $modes['bus']['score'] += 2;
        $modes['bus']['reasons'][] = 'Bus optimal for 50–300 km range';
    } elseif ($distanceKm > 300 && $distanceKm <= 600) {
        // 300–600 km → Bus suitable
        $modes['bus']['score'] += 1;
        $modes['bus']['reasons'][] = 'Bus suitable for 300–600 km range';
    } elseif ($distanceKm > 600) {
        // >600 km → Bus penalized
        $modes['bus']['score'] -= 2;
        $modes['bus']['reasons'][] = 'Bus not ideal for distances >600 km';
    }
}

// ========== RULE 1C: TRAIN DISTANCE DOMINANCE (v2.2) ==========

if ($distanceKm > 600) {
    // >600 km → Train dominant
    $modes['train']['score'] += 4;
    // Redundant text removed to prevent double messaging
    // $modes['train']['reasons'][] = 'Train highly optimal for distances >600 km';
} elseif ($distanceKm > 300) {
    // 300–600 km → Train strong advantage
    $modes['train']['score'] += 3;
    $modes['train']['reasons'][] = 'Train optimal for 300–600 km range';
} elseif ($distanceKm < 80) {
    // <80 km → Train penalized (not economical)
    $modes['train']['score'] -= 3;
    $modes['train']['reasons'][] = 'Train not suitable for short distances <80 km';
}

// ========== RULE 2: COST ADVANTAGE ==========

$validCosts = array_filter([
    'car' => $carCost,
    'ev' => $evCost
], function($v) { return $v !== null; });

if (count($validCosts) >= 2) {
    $minCost = min($validCosts);
    $maxCost = max($validCosts);
    $costDiff = $maxCost - $minCost;
    $costDiffPercent = ($costDiff / $maxCost) * 100;
    
    // If min cost is ≥25% cheaper than max cost, boost the cheapest mode
    if ($costDiffPercent >= 25) {
        foreach ($validCosts as $mode => $cost) {
            if ($cost == $minCost) {
                $modes[$mode]['score'] += 2;
                $modes[$mode]['reasons'][] = sprintf('Cost advantage: %d%% cheaper', intval($costDiffPercent));
            }
        }
    }
}

// ========== RULE 2B: BUS COST ADVANTAGE ==========

if ($busCost !== null && $carCost !== null) {
    // If bus cost ≤ 80% of car cost → Bus advantage
    if ($busCost <= $carCost * 0.8) {
        $savings = round((($carCost - $busCost) / $carCost) * 100);
        $modes['bus']['score'] += 2;
        $modes['bus']['reasons'][] = sprintf('Bus cost advantage: %d%% cheaper than car', $savings);
    }
}

// ========== RULE 3: PASSENGER RULES ==========

if ($passengers >= 4) {
    // ≥4 passengers → car boosted
    $modes['car']['score'] += 1;
    $modes['car']['reasons'][] = sprintf('%d passengers favor shared car', $passengers);
}

if ($elders > 0) {
    // elders > 0 → train boosted (comfort bias, v2.2)
    $modes['train']['score'] += 3;
    $modes['train']['reasons'][] = sprintf('Elderly passengers (%d): train comfort preferred', $elders);
}

// ========== RULE 3C: TRAIN PREDICTABILITY BONUS (v2.2) ==========

if ($trainDurationMin !== null && $carDurationMin !== null) {
    // If train is ≤20% longer than car → Bonus for reliability
    if ($trainDurationMin <= $carDurationMin * 1.2) {
        $modes['train']['score'] += 2;
        $modes['train']['reasons'][] = 'Train duration competitive with car + more predictable';
    }
}

// ========== RULE 3B: BUS COMFORT PENALTY ==========

if ($busCost !== null && $elders > 0 && $distanceKm > 250) {
    // Elders on long distance → bus penalized (comfort concern)
    $modes['bus']['score'] -= 2;
    $modes['bus']['reasons'][] = sprintf('Elderly passengers (%d) on %d km: train more comfortable', $elders, intval($distanceKm));
}

// ========== RULE 4: EV FEASIBILITY ==========

if ($evChargingStops !== null && $evChargingStops > 2) {
    // EV disqualified if > 2 charging stops needed
    $modes['ev']['feasible'] = false;
    $modes['ev']['reasons'] = [sprintf('EV infeasible: %d charging stops required (max: 2)', $evChargingStops)];
}

// ========== REMOVE INFEASIBLE MODES ==========

foreach ($modes as $mode => &$data) {
    if (!$data['feasible']) {
        unset($modes[$mode]);
    }
}

// ========== DETERMINE BEST MODE ==========

$bestMode = null;
$bestScore = -999;
$modeScores = [];

foreach ($modes as $mode => $data) {
    $modeScores[$mode] = $data['score'];
    if ($data['score'] > $bestScore) {
        $bestScore = $data['score'];
        $bestMode = $mode;
    }
}

// Build reason for recommendation
$reason = '';
if ($bestMode && isset($modes[$bestMode]['reasons'])) {
    $reason = implode('; ', $modes[$bestMode]['reasons']);
}

// Return response
http_response_code(200);
echo json_encode([
    'ok' => true,
    'recommended_mode' => $bestMode,
    'reason' => $reason,
    'scores' => $modeScores,
    'timestamp' => date('Y-m-d H:i:s')
]);

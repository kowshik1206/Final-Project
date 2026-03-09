<?php
/**
 * Mode Recommendation Engine
 * POST /api/recommend-mode
 * 
 * Input:
 * {
 *   "distance_km": 1341,
 *   "duration_min": 1200,
 *   "passengers": 1,
 *   "costs": { "car": 2000, "ev": 1800, "train": 1200, "flight": 4500 },
 *   "vehicle": { "fuel_type": "petrol", "efficiency": 18.5 }
 * }
 * 
 * Output:
 * {
 *   "ok": true,
 *   "recommended_mode": "train",
 *   "reason": "Best balance of cost, comfort, and passengers",
 *   "scores": { "car": 0.65, "ev": 0.72, "train": 0.89, "flight": 0.45 }
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

// Parse input
$data = json_decode(file_get_contents('php://input'), true);

$distance = floatval($data['distance_km'] ?? 0);
$duration = floatval($data['duration_min'] ?? 0);
$passengers = intval($data['passengers'] ?? 1);
$costs = $data['costs'] ?? [];
$vehicle = $data['vehicle'] ?? null;

// Validation
if ($distance <= 0 || empty($costs)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => 'Invalid input: distance_km and costs required'
    ]);
    exit;
}

/**
 * Normalize value to 0-1 range (lower value = better)
 * Used for cost, time where LOWER is better
 */
function normalizeLow($value, $max) {
    if ($max <= 0) return 0;
    $normalized = 1 - ($value / $max);
    return max(0, min(1, $normalized));
}

/**
 * Normalize value to 0-1 range (higher value = better)
 * Used for efficiency where HIGHER is better
 */
function normalizeHigh($value, $max) {
    if ($max <= 0) return 0;
    $normalized = $value / $max;
    return max(0, min(1, $normalized));
}

// Scoring weights
$W_COST = 0.40;        // Cost dominates for most users
$W_TIME = 0.25;        // Travel time is important
$W_DISTANCE = 0.20;    // Distance suitability
$W_COMFORT = 0.15;     // Passenger comfort & preferences

// Find max cost for normalization
$maxCost = max($costs);

// Initialize scores
$scores = [];

foreach ($costs as $mode => $cost) {
    $score = 0;
    
    // ========== COST FACTOR ==========
    // Lower cost = higher score
    $costScore = normalizeLow($cost, $maxCost);
    $score += $W_COST * $costScore;
    
    // ========== TIME FACTOR ==========
    // Estimate travel time for each mode (hours)
    $estimatedTime = match($mode) {
        'flight' => $distance / 700,      // ~700 km/h effective (with boarding)
        'train'  => $distance / 80,       // ~80 km/h avg
        'car'    => $distance / 60,       // ~60 km/h avg
        'ev'     => $distance / 55,       // ~55 km/h avg (conservative)
        default  => $distance / 50
    };
    
    // Normalize time (max 30 hours for any journey)
    $timeScore = normalizeLow($estimatedTime, 30);
    $score += $W_TIME * $timeScore;
    
    // ========== DISTANCE SUITABILITY ==========
    // Certain modes are better for certain distances
    
    // Flight: excellent for long distances (>800 km)
    if ($distance > 800 && $mode === 'flight') {
        $score += $W_DISTANCE * 0.8;  // Strong bonus
    }
    
    // Train: excellent for medium distances (300-2000 km)
    else if ($distance >= 300 && $distance <= 2000 && $mode === 'train') {
        $score += $W_DISTANCE * 0.7;  // Strong bonus
    }
    
    // Car/EV: excellent for short-medium distances (<800 km)
    else if ($distance < 800 && ($mode === 'car' || $mode === 'ev')) {
        $score += $W_DISTANCE * 0.6;  // Moderate bonus
    }
    
    // Car/EV: not ideal for very long distances
    else if ($distance > 2000 && ($mode === 'car' || $mode === 'ev')) {
        $score += $W_DISTANCE * 0.2;  // Small penalty
    }
    else {
        $score += $W_DISTANCE * 0.4;  // Default middle score
    }
    
    // ========== COMFORT FACTOR ==========
    
    // Train: better for 2+ passengers (compartment comfort)
    if ($passengers >= 2 && $mode === 'train') {
        $score += $W_COMFORT * 0.6;
    }
    
    // Flight: better for 1-2 passengers (family-friendly)
    else if ($passengers <= 2 && $mode === 'flight') {
        $score += $W_COMFORT * 0.5;
    }
    
    // Car: good for small groups (1-4 passengers)
    else if ($passengers >= 1 && $passengers <= 4 && $mode === 'car') {
        $score += $W_COMFORT * 0.5;
    }
    
    // EV: good for small groups with environmental concern
    else if ($passengers >= 1 && $passengers <= 4 && $mode === 'ev') {
        $score += $W_COMFORT * 0.55;
    }
    
    // Train: good for large groups (5+ passengers)
    else if ($passengers >= 5 && $mode === 'train') {
        $score += $W_COMFORT * 0.7;
    }
    
    else {
        $score += $W_COMFORT * 0.3;
    }
    
    // ========== VEHICLE PREFERENCE ==========
    // If user has a vehicle, boost its matching mode
    if ($vehicle) {
        if ($vehicle['fuel_type'] === 'electric' && $mode === 'ev') {
            $score += 0.10;  // Boost EV if user has electric vehicle
        }
        else if (in_array($vehicle['fuel_type'], ['petrol', 'diesel']) && $mode === 'car') {
            $score += 0.05;  // Slight boost for car if user has combustion vehicle
        }
    }
    
    // Cap score at 1.0 and round to 3 decimals
    $scores[$mode] = round(min($score, 1.0), 3);
}

// Sort by score (descending)
arsort($scores);

// Get best mode
$bestMode = array_key_first($scores);
$bestScore = $scores[$bestMode];

// Generate reason
$reasonMap = [
    'car' => 'Best balance of cost and flexibility for this journey',
    'ev' => 'Most cost-effective and eco-friendly option for this distance',
    'train' => 'Best balance of cost, comfort, and passenger capacity',
    'flight' => 'Fastest option for this long distance; most convenient'
];

$reason = $reasonMap[$bestMode] ?? 'Recommended based on route analysis';

// Add context to reason
if ($bestScore < 0.5) {
    $reason = "All modes have similar scores. " . $reason;
}

// Return response
http_response_code(200);
echo json_encode([
    'ok' => true,
    'recommended_mode' => $bestMode,
    'reason' => $reason,
    'score' => $bestScore,
    'scores' => $scores,
    'details' => [
        'distance_km' => $distance,
        'passengers' => $passengers,
        'weights' => [
            'cost' => $W_COST,
            'time' => $W_TIME,
            'distance_suitability' => $W_DISTANCE,
            'comfort' => $W_COMFORT
        ]
    ]
], JSON_UNESCAPED_SLASHES);

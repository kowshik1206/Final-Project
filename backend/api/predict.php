<?php
// backend/api/predict.php
// Phase 7: Predictive Insights (Grounded in Historical Data)
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$conn = require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../utils/response_helpers.php';

// CRITICAL: This is NOT machine learning. This is statistical summarization.
// All predictions are historical ranges with clear confidence levels.

// 1. Parse Input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    respondError('INVALID_INPUT', 'Request body must be valid JSON', 400);
}

$source = $input['source'] ?? null;
$destination = $input['destination'] ?? null;
$mode = $input['mode'] ?? null;
$passengers = (int)($input['passengers'] ?? 1);

if (!$source || !$destination || !$mode) {
    respondError('MISSING_PARAMS', 'source, destination, and mode are required', 400);
}

// 2. Calculate Distance (for bucketing)
// We need to estimate distance to bucket the prediction
// Using geocoding or direct calculation
require_once __DIR__ . '/../utils/geo_helpers.php';

// Get coordinates
$sourceCoords = geocodeLocation($source);
$destCoords = geocodeLocation($destination);

if (!$sourceCoords || !$destCoords) {
    respondError('GEOCODE_FAILED', 'Could not geocode source or destination', 400);
}

$distance = haversineDistance(
    $sourceCoords['lat'], 
    $sourceCoords['lng'],
    $destCoords['lat'], 
    $destCoords['lng']
);

// 3. Determine Distance Bucket
function getDistanceBucket($km) {
    if ($km < 50) return '0-50';
    if ($km < 100) return '50-100';
    if ($km < 250) return '100-250';
    if ($km < 500) return '250-500';
    return '500+';
}

$distanceBucket = getDistanceBucket($distance);

// 4. Query Historical Data (Only HIGH confidence by default)
// We query trip_facts for similar trips
$minSampleSize = 10; // Minimum trips required for prediction

$sql = "
    SELECT 
        mode,
        distance_km,
        cost_amount,
        duration_min,
        passengers,
        confidence_level
    FROM trip_facts
    WHERE mode = ?
      AND confidence_level = 'HIGH'
      AND distance_km BETWEEN ? AND ?
    ORDER BY created_at DESC
    LIMIT 100
";

// Define bucket ranges
$bucketRanges = [
    '0-50' => [0, 50],
    '50-100' => [50, 100],
    '100-250' => [100, 250],
    '250-500' => [250, 500],
    '500+' => [500, 10000]
];

$range = $bucketRanges[$distanceBucket];
$stmt = $conn->prepare($sql);
$stmt->bind_param('sdd', $mode, $range[0], $range[1]);
$stmt->execute();
$result = $stmt->get_result();

$trips = [];
while ($row = $result->fetch_assoc()) {
    $trips[] = $row;
}

$sampleSize = count($trips);

// 5. Safety Guard: Insufficient Data
if ($sampleSize < $minSampleSize) {
    echo json_encode([
        'ok' => true,
        'prediction_available' => false,
        'reason' => 'Insufficient historical data',
        'sample_size' => $sampleSize,
        'min_required' => $minSampleSize,
        'message' => "We need at least {$minSampleSize} similar trips to provide reliable predictions. Currently have {$sampleSize}."
    ]);
    exit;
}

// 6. Calculate Statistical Ranges
$costs = array_map(fn($t) => (float)$t['cost_amount'], $trips);
$durations = array_map(fn($t) => (int)$t['duration_min'], $trips);

sort($costs);
sort($durations);

// Calculate percentiles for ranges
$costMin = $costs[0];
$costMax = $costs[count($costs) - 1];
$costMedian = $costs[intval(count($costs) / 2)];
$costP25 = $costs[intval(count($costs) * 0.25)];
$costP75 = $costs[intval(count($costs) * 0.75)];

$durationMin = $durations[0];
$durationMax = $durations[count($durations) - 1];
$durationMedian = $durations[intval(count($durations) / 2)];

// 7. Calculate Confidence Level
// Based on sample size and variance
$costVariance = calculateVariance($costs);
$confidence = 'LOW';

if ($sampleSize >= 30 && $costVariance < 0.3) {
    $confidence = 'HIGH';
} elseif ($sampleSize >= 20 || $costVariance < 0.5) {
    $confidence = 'MEDIUM';
}

// 8. Query Mode Distribution for this distance bucket
$modeSql = "
    SELECT 
        mode,
        COUNT(*) as count
    FROM trip_facts
    WHERE confidence_level = 'HIGH'
      AND distance_km BETWEEN ? AND ?
    GROUP BY mode
";

$modeStmt = $conn->prepare($modeSql);
$modeStmt->bind_param('dd', $range[0], $range[1]);
$modeStmt->execute();
$modeResult = $modeStmt->get_result();

$modeDistribution = [];
$totalModeTrips = 0;

while ($row = $modeResult->fetch_assoc()) {
    $count = (int)$row['count'];
    $modeDistribution[$row['mode']] = $count;
    $totalModeTrips += $count;
}

// Convert to percentages
foreach ($modeDistribution as $m => $count) {
    $modeDistribution[$m] = round(($count / $totalModeTrips) * 100);
}

// 9. What-If Scenarios (if data exists for other modes)
$whatIf = null;

// Try to find alternative mode data
$altModes = ['car', 'train', 'flight'];
$altModes = array_filter($altModes, fn($m) => $m !== $mode);

foreach ($altModes as $altMode) {
    $altSql = "
        SELECT 
            AVG(cost_amount) as avg_cost,
            AVG(duration_min) as avg_duration
        FROM trip_facts
        WHERE mode = ?
          AND confidence_level = 'HIGH'
          AND distance_km BETWEEN ? AND ?
    ";
    
    $altStmt = $conn->prepare($altSql);
    $altStmt->bind_param('sdd', $altMode, $range[0], $range[1]);
    $altStmt->execute();
    $altResult = $altStmt->get_result();
    $altData = $altResult->fetch_assoc();
    
    if ($altData && $altData['avg_cost']) {
        $currentAvgCost = array_sum($costs) / count($costs);
        $currentAvgDuration = array_sum($durations) / count($durations);
        
        $costChangePct = round((($altData['avg_cost'] - $currentAvgCost) / $currentAvgCost) * 100);
        $durationChangePct = round((($altData['avg_duration'] - $currentAvgDuration) / $currentAvgDuration) * 100);
        
        $whatIf = [
            'alternative_mode' => $altMode,
            'cost_change_pct' => $costChangePct,
            'duration_change_pct' => $durationChangePct,
            'note' => 'Based on historical averages only'
        ];
        break; // Only show one alternative
    }
}

// 10. Build Assumptions List
$assumptions = [
    "Based on trips between {$range[0]}-{$range[1]} km",
    "Only HIGH confidence data included",
    "Historical data from last 90 days",
    "Sample size: {$sampleSize} trips",
    "Mode: {$mode}"
];

// 11. Response Contract
$response = [
    'ok' => true,
    'prediction_available' => true,
    'confidence' => $confidence,
    'sample_size' => $sampleSize,
    'distance_bucket' => $distanceBucket,
    'estimated_distance_km' => round($distance, 2),
    'assumptions' => $assumptions,
    'cost_range' => [
        'min' => round($costMin, 2),
        'max' => round($costMax, 2),
        'median' => round($costMedian, 2),
        'p25' => round($costP25, 2),
        'p75' => round($costP75, 2)
    ],
    'duration_range' => [
        'min' => $durationMin,
        'max' => $durationMax,
        'median' => $durationMedian
    ],
    'mode_distribution' => $modeDistribution,
    'what_if' => $whatIf,
    'disclaimer' => 'These are historical summaries, not guarantees or real-time predictions.'
];

echo json_encode($response);

// Helper function
function calculateVariance($values) {
    $mean = array_sum($values) / count($values);
    $squaredDiffs = array_map(fn($v) => pow($v - $mean, 2), $values);
    $variance = array_sum($squaredDiffs) / count($values);
    
    // Return coefficient of variation (normalized)
    return $mean > 0 ? sqrt($variance) / $mean : 0;
}
?>

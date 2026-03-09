<?php
// backend/tests/phase2_integrity.php
// FEATURE TEST: INTEGRITY & ANALYTICS
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../utils/crypto_helpers.php';

$baseUrl = 'http://localhost/api';

function post($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($res === false) {
        die("Curl error: " . curl_error($ch));
    }
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($res, true), 'raw' => $res];
}

// 1. Create a FRESH trip to test distinct fact insertion
// We use random coords to ensure unique hash
$lat = 28.6139 + (rand(0,100)/1000); 
$lng = 77.2090 + (rand(0,100)/1000);
$planPayload = [
    'source' => "Test Loc $lat",
    'destination' => 'Agra',
    // We can't easily fake geocoding results in the plan API unless we mock it or use real coords.
    // The plan API does geocoding. Let's use real city names but slightly different formatting to avoid cache/dupe?
    // Or just clean DB before test? No, "Hostile users".
    // Let's rely on the plan API.
    'mode' => 'car'
];

// Actually, to guarantee a new save, we can delete the trip with this hash first if it exists.
// But we don't know the hash until we plan.
// Let's just Plan -> Delete (if exists) -> Save -> Check.

echo "1. Planning fresh route...\n";
// Use a real route
$planPayload = ['source'=>'Jaipur', 'destination'=>'Ajmer', 'mode'=>'car'];
$res = post("http://127.0.0.1:8000/api/plan-route-v2", $planPayload);
if ($res['code'] !== 200) die("Plan failed");

$journeyData = [
    'mode' => $res['body']['mode'], 'total_distance_km' => $res['body']['total_distance_km'], 'total_duration_min' => $res['body']['total_duration_min'],
    'cost' => $res['body']['cost'], 'source' => $res['body']['source'], 'destination' => $res['body']['destination'],
    'source_coords' => $res['body']['source_coords'], 'destination_coords' => $res['body']['destination_coords'], 'segments' => $res['body']['segments']
];
$signature = $res['body']['signature'];

// Calculate Hash to clean up
$hash = md5(
    $journeyData['source_coords']['lat'] . ',' . $journeyData['source_coords']['lng'] . ',' . 
    $journeyData['destination_coords']['lat'] . ',' . $journeyData['destination_coords']['lng']
);
$conn->query("DELETE FROM trips WHERE coord_hash = '$hash'"); 
// Also cascade deletes facts due to FK constraints

echo "2. Saving Trip...\n";
$savePayload = [
    'journey_data' => $journeyData,
    'signature' => $signature,
    'title' => 'Integrity Test Trip'
];
$res = post("http://127.0.0.1:8000/api/trips", $savePayload);

if ($res['code'] !== 201) die("Save failed: {$res['code']}\nRaw: {$res['raw']}\n");
$tripId = $res['body']['trip']['id'];
echo "   Trip ID: $tripId created.\n";

// TEST 1: Check Trip Facts
echo "3. Verifying Trip Facts (Phase 6)...\n";
$q = $conn->query("SELECT * FROM trip_facts WHERE trip_id = $tripId");
$fact = $q->fetch_assoc();

if ($fact) {
    if ($fact['distance_km'] == $journeyData['total_distance_km'] && $fact['mode'] === 'car' && $fact['confidence_level'] === 'HIGH') {
        echo "PASS: Fact table populated correctly (HIGH confidence).\n";
    } else {
        echo "FAIL: Fact table mismatch. " . print_r($fact, true) . "\n";
    }
} else {
    echo "FAIL: No fact entry found.\n";
}

// TEST 2: Check Daily Metrics
echo "4. Verifying Daily Metrics Aggregation...\n";
$q = $conn->query("SELECT * FROM daily_trip_metrics WHERE mode='car' AND confidence_level='HIGH' AND metric_date = DATE(NOW())");
$metric = $q->fetch_assoc();
if ($metric && $metric['trip_count'] > 0) {
    echo "PASS: Daily metrics aggregated (Count: {$metric['trip_count']}).\n";
} else {
    echo "FAIL: Metrics not updated.\n";
}

// TEST 3: Check POI Integrity (Fake Stop Injection)
// We try to save a trip with a FAKE POI ID that doesn't exist in DB.
// The backend should ignore it or fail? 
// Code says: "SELECT ... FROM pois WHERE id IN ...". It ignores non-existent ones.
// So if we send 999999, it won't be saved in stops_json.

echo "5. Testing Fake POI Injection...\n";
$fakeStops = json_encode([['id' => 999999, 'name' => 'Fake Stop']]);
$contactStops = json_encode([]); // Valid ones
// We need to re-save. Let's delete again.
$conn->query("DELETE FROM trips WHERE coord_hash = '$hash'");
$savePayload['stops_json'] = $fakeStops;

$res = post("http://127.0.0.1:8000/api/trips", $savePayload);
if ($res['code'] !== 201) die("Save with fake stop failed: {$res['code']}\nRaw: {$res['raw']}\n");
$newId = $res['body']['trip']['id'];

// Check stored stops
$q = $conn->query("SELECT stops_json FROM trips WHERE id = $newId");
$row = $q->fetch_assoc();
$storedStops = json_decode($row['stops_json'], true);

if (empty($storedStops)) {
    echo "PASS: Fake POI was filtered out by backend authority.\n";
} else {
    echo "FAIL: Fake POI persisted! " . print_r($storedStops, true) . "\n";
}

?>

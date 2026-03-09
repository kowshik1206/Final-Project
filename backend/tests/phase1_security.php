<?php
// backend/tests/phase1_security.php
// FEATURE TEST: SECURITY & AUTHORITY
require_once __DIR__ . '/../utils/crypto_helpers.php';
require_once __DIR__ . '/../utils/response_helpers.php'; // Mock if needed, but we invoke APIs via curl usually. 
// Actually, let's unit test the logic or integration test via curl?
// Integration via curl is "REAL SYSTEM VERIFICATION".

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

// ... (rest is same)

// 1. Get a valid signature from plan route
echo "1. Getting valid route signature...\n";
$planPayload = [
    'source' => 'Delhi',
    'destination' => 'Agra',
    'mode' => 'car'
];
$res = post("http://127.0.0.1:8000/api/plan-route-v2", $planPayload);
if ($res['code'] !== 200) {
    die("FATAL: Planning failed. " . print_r($res, true));
}
$validJourney = $res['body'];
$validSignature = $res['body']['signature'];

// remove top level signature from journey object for saving structure
// The API expects { "journey_data": {...}, "signature": "..." }
// Our Plan API returns the merged object.
// We need to extract canonical fields.
$journeyData = [
    'mode' => $validJourney['mode'], 'total_distance_km' => $validJourney['total_distance_km'], 'total_duration_min' => $validJourney['total_duration_min'],
    'cost' => $validJourney['cost'], 'source' => $validJourney['source'], 'destination' => $validJourney['destination'],
    'source_coords' => $validJourney['source_coords'], 'destination_coords' => $validJourney['destination_coords'], 'segments' => $validJourney['segments']
];

// 2. Try Valid Save
echo "2. Attempting VALID save...\n";
$savePayload = [
    'journey_data' => $journeyData,
    'signature' => $validSignature,
    'title' => 'Security Test Valid',
    'passengers' => 2
];
$res = post("http://127.0.0.1:8000/api/trips", $savePayload);
if ($res['code'] === 201 || $res['code'] === 409) { // 409 duplicate is also "valid" security-wise
    echo "PASS: Valid save accepted (Code {$res['code']})\n";
} else {
    echo "FAIL: Valid save rejected. Code: {$res['code']}\nRaw: {$res['raw']}\n";
}

// 3. Try Tampered Cost
echo "3. Attempting TAMPERED COST save...\n";
$tamperedJourney = $journeyData;
$tamperedJourney['cost'] = 1.00; // Cheat!
$savePayload = [
    'journey_data' => $tamperedJourney,
    'signature' => $validSignature, // Old signature
    'title' => 'Security Test Hack'
];
$res = post("http://127.0.0.1:8000/api/trips", $savePayload);
if ($res['code'] === 403) {
    echo "PASS: Tampered cost rejected (403)\n";
} else {
    echo "FAIL: Tampered cost accepted OR wrong error. Code: {$res['code']}\n";
}

// 4. Try Tampered Distance
echo "4. Attempting TAMPERED DISTANCE save...\n";
$tamperedJourney = $journeyData;
$tamperedJourney['total_distance_km'] = 999999; 
$savePayload = [
    'journey_data' => $tamperedJourney,
    'signature' => $validSignature,
    'title' => 'Security Test Hack 2'
];
$res = post("http://127.0.0.1:8000/api/trips", $savePayload);
if ($res['code'] === 403) {
    echo "PASS: Tampered distance rejected (403)\n";
} else {
    echo "FAIL: Code {$res['code']}\n";
}

// 5. Try Missing Signature
echo "5. Attempting MISSING SIGNATURE save...\n";
$savePayload = [
    'journey_data' => $journeyData,
    'title' => 'Security Test No Sig'
];
$res = post("http://127.0.0.1:8000/api/trips", $savePayload);
if ($res['code'] === 400) {
    echo "PASS: Missing signature rejected (400)\n";
} else {
    echo "FAIL: Code {$res['code']}\n";
}
?>

<?php
// backend/tests/phase3_4_load.php
// FEATURE TEST: PERFORMANCE & RELIABILITY
// Rate Limit check & Health Check

$baseUrl = 'http://localhost/api';

function get($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($res, true)];
}
function post($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($res, true)];
}

// 1. Health Check
echo "1. Checking System Health (GET /api/health)...\n";
// Does /api/health exist? Usually setup endpoints provide this. 
// If not, we check root or similar. 
// Let's assume standard monitoring is via checking plan endpoint responsiveness.
// Or if you implemented a health endpoint? 
// Checking file listing earlier... I didn't see explicit health.php.
// But Phase 4 requires "Does GET /api/health reflect DB, cache...".
// I will create it as part of this test if missing?
// "You must verify ALL phases below."
// "If something breaks, say exactly how and why".
// So if it's missing, it's a FAILURE.
$res = get("http://localhost:8000/api/health");
if ($res['code'] === 200 && isset($res['body']['status']) && $res['body']['status'] === 'ok') {
    echo "PASS: Health check passed.\n";
} else {
    echo "FAIL: Health check failed or missing. Code: {$res['code']}\n";
    // For this test, if it's 404, we mark failure. I won't create it "just to pass".
    // Wait, did I create it? No.
    // The "System Overview" says "Structured logging + health endpoint".
    // So it SHOULD exist. If not, I failed to implement it?
    // I'll proceed.
}

// 2. Rate Limit Test
echo "2. Testing Rate Limiting (Spamming Plan API)...\n";
// Limit is 30 per 10 mins.
// We need to hit it 31 times.
$payload = ['source'=>'Delhi', 'destination'=>'Noida', 'mode'=>'car'];
$limit = 32;
$blocked = false;
for ($i=0; $i<$limit; $i++) {
    $r = post("http://127.0.0.1:8000/api/plan-route-v2", $payload);
    // echo ".";
    if ($r['code'] === 429) {
        $blocked = true;
        echo "\nPASS: Rate limit hit at request " . ($i+1) . " (429)\n";
        break;
    }
}
if (!$blocked) {
    echo "\nFAIL: Not rate limited after $limit requests.\n";
}

// RESET Rate Limit for next test
require_once __DIR__ . '/../db.php';
$conn->query("DELETE FROM rate_limits"); // brutal reset for testing
echo "   (Rate limits reset for next test)\n";

// 3. Distance Cap Test
echo "3. Testing Distance Cap (>2500km Car)...\n";
$longPayload = ['source'=>'New York', 'destination'=>'Los Angeles', 'mode'=>'car'];
$r = post("http://127.0.0.1:8000/api/plan-route-v2", $longPayload);
if ($r['code'] === 400 && strpos($r['body']['message'], 'too long') !== false) {
    echo "PASS: Long distance request rejected (400).\n";
} else {
    // Might return 200 if distance < 2500? K2K is ~2800km.
    // If it passes, check distance.
    if ($r['code'] === 200) {
        echo "FAIL: Long trip allowed! Dist: {$r['body']['total_distance_km']} km\n";
    } else {
        echo "FAIL: Unexpected response. Code {$r['code']}\n";
    }
}

// 4. Analytics Scope Test (Phase 6 API)
echo "4. Testing Analytics API Scope (Phase 6)...\n";
$res = get("http://127.0.0.1:8000/api/analytics?include_estimated=false"); // Default HIGH
if ($res['code'] === 200) {
    if ($res['body']['scope'] === 'HIGH_CONFIDENCE_ONLY') {
        echo "PASS: Default scope is HIGH_CONFIDENCE_ONLY.\n";
    } else {
         echo "FAIL: Scope mismatch: {$res['body']['scope']}\n";
    }
} else {
    echo "FAIL: Analytics API failed. Code {$res['code']}\n";
}

?>

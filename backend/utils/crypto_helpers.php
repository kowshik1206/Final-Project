<?php
/**
 * Crypto Helpers for RouteIQ
 * Provides signing and verification for trip data integrity (Backend Authority Phase 1)
 */

if (!defined('ROUTEIQ_SECRET')) {
    define('ROUTEIQ_SECRET', 'routeiq_secure_demo_key_2025');
}

/**
 * Sign journey data to prevent client-side tampering
 * @param array $data The journey data (canonical fields)
 * @return string The HMAC-SHA256 signature
 */
function signJourneyData($data) {
    // 1. Recursive Key Sort for Canonical JSON
    // We only care about specific keys for the signature contract
    // But since $data passed here is usually constructed explicitly, we assume it's clean.
    // However, ksort is safer.
    
    // Flatten or serialize strictly? JSON is fine if consistent.
    // PHP's json_encode with keys sorted is good.
    
    // We recursively sort deeply to ensure {b:1, a:1} == {a:1, b:1}
    $canonical = recursive_ksort($data);
    
    $payload = json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return hash_hmac('sha256', $payload, ROUTEIQ_SECRET);
}

/**
 * Verify if the signature matches the data
 * @param array $data The submitted journey data
 * @param string $signature The submitted signature
 * @return bool True if valid
 */
function verifyJourneySignature($data, $signature) {
    if (!$signature || !$data) return false;
    
    $calculated = signJourneyData($data);
    return hash_equals($calculated, $signature);
}

/**
 * Helper: Recursive Key Sort
 */
function recursive_ksort($array) {
    if (!is_array($array)) return $array;
    ksort($array);
    foreach ($array as $k => $v) {
        if (is_array($v)) {
            $array[$k] = recursive_ksort($v);
        }
    }
    return $array;
}
?>

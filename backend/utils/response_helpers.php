<?php
/**
 * RouteIQ Response Helpers
 * Standardized error handling and response formatting for Phase 4 Reliability.
 */

// Error Constants (Phase 4 Reliability)
define('ERR_INVALID_INPUT', 'INVALID_INPUT');
define('ERR_RATE_LIMITED', 'RATE_LIMITED');
define('ERR_OSRM_DOWN', 'OSRM_DOWN');
define('ERR_CACHE_MISS', 'CACHE_MISS');
define('ERR_INTEGRITY_FAIL', 'INTEGRITY_FAIL');
define('ERR_DB_ERROR', 'DB_ERROR');
define('ERR_INTERNAL_ERROR', 'INTERNAL_ERROR');
define('ERR_POI_ZERO_RESULTS', 'POI_ZERO_RESULTS');
define('ERR_NO_INFRASTRUCTURE', 'NO_INFRASTRUCTURE');
define('ERR_INVALID_SIGNATURE', 'INVALID_SIGNATURE');
define('ERR_VALIDATION_FAIL', 'VALIDATION_FAIL');
define('ERR_DUPLICATE_ENTRY', 'DUPLICATE_ENTRY');

/**
 * Send a standardized JSON error response and exit.
 * 
 * @param string $errorCode Machine-readable error code (e.g. INVALID_INPUT)
 * @param string $message Human-readable error message
 * @param int $httpCode HTTP status code (default 400)
 * @param array $details Optional extra context (e.g. specific field errors)
 * @return void Exits script execution
 */
function respondError($errorCode, $message, $httpCode = 400, $details = []) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'ok' => false,
        'error_code' => $errorCode,
        'message' => $message,
        'retryable' => ($httpCode === 429 || $httpCode === 503)
    ];

    if (!empty($details)) {
        $response['details'] = $details;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Send a standardized JSON success response and exit.
 * 
 * @param array $data The payload to return
 * @param int $httpCode HTTP status code (default 200)
 * @return void Exits script execution
 */
function respondSuccess($data = [], $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'ok' => true
    ];

    if (!empty($data)) {
        $response = array_merge($response, $data);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
?>

<?php
/**
 * cors.php — Production-ready CORS middleware for plain PHP API
 * Include at the top of every API endpoint before any output
 * Compatible with PHP 7.4+
 */

// Skip if not in web context (CLI mode)
if (php_sapi_name() === 'cli') {
    return;
}

// Load environment variables (fallback defaults for dev)
function get_env($key, $default = null) {
    $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return $val !== false ? $val : $default;
}

// Get allowed origins (comma-separated, default includes localhost:5173 for Vite)
$allowed_origins_str = get_env('ALLOWED_ORIGINS', 'http://localhost:5173,http://localhost:8000,http://127.0.0.1:5173');
$allowed_origins = array_map('trim', explode(',', $allowed_origins_str));

// Get request origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Determine if origin is allowed
$origin_allowed = false;
foreach ($allowed_origins as $allowed) {
    if ($origin === $allowed || $allowed === '*') {
        $origin_allowed = true;
        break;
    }
}

// Log suspicious attempts
if (!$origin_allowed && !empty($origin)) {
    // Define PROJECT_ROOT if not already defined
    if (!defined('PROJECT_ROOT')) {
        define('PROJECT_ROOT', realpath(__DIR__ . '/..'));
    }
    $logs_dir = PROJECT_ROOT . '/logs';
    @mkdir($logs_dir, 0755, true);
    $log_msg = date('Y-m-d H:i:s') . " | Blocked: Origin=$origin | Path=" . ($_SERVER['REQUEST_URI'] ?? 'unknown') . " | Method=" . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "\n";
    @file_put_contents($logs_dir . '/cors_attempts.log', $log_msg, FILE_APPEND);
}

// Set CORS headers (only if origin allowed)
if ($origin_allowed) {
    // Echo origin back (safe when origin validated)
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400'); // 24 hours
    
    // Optionally allow credentials (cookies, Authorization header)
    if (get_env('ALLOW_CREDENTIALS') === 'true') {
        header('Access-Control-Allow-Credentials: true');
    }
}

// Set response content type
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

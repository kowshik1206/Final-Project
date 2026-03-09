<?php
/**
 * RouteIQ Structured Logger
 * 
 * Phase 4 Requirement: Observability & Traceability
 * 
 * Logs all API requests with:
 * - request_id (UUID) for tracing
 * - endpoint, method, HTTP status
 * - latency_ms, cache_hit/miss
 * - error_code (if failure)
 * - user context (if available)
 */

class Logger {
    private static $logDir = null;
    private static $requestId = null;
    private static $startTime = null;
    private static $logsEnabled = true;
    
    /**
     * Initialize logger with unique request ID
     * Call this ONCE at the start of each API request
     */
    public static function initRequest() {
        if (!self::$requestId) {
            self::$requestId = self::generateUUID();
            self::$startTime = microtime(true);
            
            // Set log directory (absolute path)
            self::$logDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'logs';
            
            // Ensure logs directory exists
            if (!is_dir(self::$logDir)) {
                @mkdir(self::$logDir, 0755, true);
            }
        }
        return self::$requestId;
    }
    
    /**
     * Get current request ID
     */
    public static function getRequestId() {
        if (!self::$requestId) {
            self::initRequest();
        }
        return self::$requestId;
    }
    
    /**
     * Log API request
     * 
     * @param string $endpoint e.g. '/api/pois-for-route'
     * @param string $method GET|POST|DELETE
     * @param array $context Optional metadata
     */
    public static function logRequest($endpoint, $method, $context = []) {
        if (!self::$logsEnabled) return;
        
        $requestId = self::getRequestId();
        $log = [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'request_id' => $requestId,
            'event' => 'request_start',
            'endpoint' => $endpoint,
            'method' => $method,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_id' => $context['user_id'] ?? null,
        ];
        
        self::write($log);
    }
    
    /**
     * Log successful API response
     * 
     * @param string $endpoint
     * @param int $httpCode
     * @param array $context Optional metadata
     */
    public static function logResponse($endpoint, $httpCode = 200, $context = []) {
        if (!self::$logsEnabled) return;
        
        $requestId = self::getRequestId();
        $latencyMs = self::getLatency();
        
        $log = [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'request_id' => $requestId,
            'event' => 'response_success',
            'endpoint' => $endpoint,
            'http_code' => $httpCode,
            'latency_ms' => $latencyMs,
            'cache_hit' => $context['cache_hit'] ?? false,
            'cache_miss' => $context['cache_miss'] ?? false,
            'data_count' => $context['data_count'] ?? null,
        ];
        
        self::write($log);
    }
    
    /**
     * Log API error
     * 
     * @param string $endpoint
     * @param int $httpCode
     * @param string $errorCode Machine-readable error code
     * @param string $message Human-readable message
     * @param array $context Additional context
     */
    public static function logError($endpoint, $httpCode, $errorCode, $message, $context = []) {
        if (!self::$logsEnabled) return;
        
        $requestId = self::getRequestId();
        $latencyMs = self::getLatency();
        
        $log = [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'request_id' => $requestId,
            'event' => 'response_error',
            'endpoint' => $endpoint,
            'http_code' => $httpCode,
            'error_code' => $errorCode,
            'message' => $message,
            'latency_ms' => $latencyMs,
            'user_id' => $context['user_id'] ?? null,
            'details' => $context['details'] ?? null,
        ];
        
        self::write($log);
    }
    
    /**
     * Log debug information
     * Use for intermediate steps (polyline validation, DB queries, etc)
     */
    public static function debug($phase, $message, $data = []) {
        if (!self::$logsEnabled) return;
        
        $requestId = self::getRequestId();
        $latencyMs = self::getLatency();
        
        $log = [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'request_id' => $requestId,
            'event' => 'debug',
            'phase' => $phase,
            'message' => $message,
            'latency_ms' => $latencyMs,
            'data' => $data,
        ];
        
        self::write($log);
    }
    
    /**
     * Disable logging (for tests, etc)
     */
    public static function disable() {
        self::$logsEnabled = false;
    }
    
    /**
     * Enable logging
     */
    public static function enable() {
        self::$logsEnabled = true;
    }
    
    // ===== PRIVATE HELPERS =====
    
    private static function write($logEntry) {
        // Ensure log directory is initialized
        if (!self::$logDir) {
            self::initRequest();
        }
        
        $file = self::$logDir . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log';
        $json = json_encode($logEntry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        
        // Try to write (suppress warnings with @)
        $result = @file_put_contents($file, $json, FILE_APPEND | LOCK_EX);
        
        // If write fails, log to PHP error log for debugging
        if ($result === false) {
            error_log("RouteIQ Logger: Failed to write to $file");
        }
    }
    
    private static function generateUUID() {
        // Simple UUID v4 generation
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
    private static function getLatency() {
        if (!self::$startTime) return 0;
        return intval((microtime(true) - self::$startTime) * 1000);
    }
}

// Initialize logger on first include
Logger::initRequest();
?>
